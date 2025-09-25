<?php
/**
 * ACF Helper functions for the WC Custom Bulk Order Plugin.
 *
 * @package WC_CBO
 * @version 1.7.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Programmatically checks if an ACF field group's location rules match a given post context.
 *
 * This function manually replicates the logic ACF uses to determine if a field group
 * should be displayed on a given screen. It checks all rule groups (OR) and all
 * rules within each group (AND).
 *
 * @since 1.6.0
 *
 * @param array $field_group    The ACF field group array.
 * @param int   $post_id        The ID of the post to check against.
 * @return bool True if the location rules match, false otherwise.
 */
function wc_cbo_check_acf_location_rules( $field_group, $post_id ) {
	if ( ! $field_group || ! $field_group['active'] || empty( $field_group['location'] ) || ! $post_id ) {
		return false;
	}

	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return false;
	}

	// A field group is visible if ANY of its rule groups match.
	foreach ( $field_group['location'] as $rule_group ) {
		if ( empty( $rule_group ) ) {
			continue;
		}

		$group_matches = true; // Assume this group matches until a rule fails.

		// A rule group matches if ALL of its individual rules match.
		foreach ( $rule_group as $rule ) {
			$match = false;

			switch ( $rule['param'] ) {
				case 'post_type':
					// In WooCommerce, 'product_type' is more specific, but ACF location rules use post_type.
					// A simple product has post_type 'product'. A variable product also has post_type 'product'.
					// The rule value from ACF will be 'product'. We check against the actual post_type.
					$post_type = get_post_type( $post_id );
					if ( '==' === $rule['operator'] ) {
						$match = ( $post_type === $rule['value'] );
					} else {
						$match = ( $post_type !== $rule['value'] );
					}
					break;

				case 'post_taxonomy':
					// Rule value is 'taxonomy:term_slug'.
					$taxonomy_parts = explode( ':', $rule['value'] );
					$taxonomy       = $taxonomy_parts[0];
					$term_slug      = $taxonomy_parts[1];
					
					// Use has_term to check if the product has the specified term (slug is a string).
					if ( '==' === $rule['operator'] ) {
						$match = has_term( $term_slug, $taxonomy, $post_id );
					} else {
						$match = ! has_term( $term_slug, $taxonomy, $post_id );
					}
					break;
				
				case 'post':
					if ( '==' === $rule['operator'] ) {
						$match = ( (int) $post_id === (int) $rule['value'] );
					} else {
						$match = ( (int) $post_id !== (int) $rule['value'] );
					}
					break;

				default:
					// Allow other rules to be added by filtering, but default to not matching.
					$match = apply_filters( 'wc_cbo_match_acf_location_rule', false, $rule, $product );
					break;
			}

			if ( ! $match ) {
				$group_matches = false; // This rule failed, so the whole group fails.
				break; // Move to the next rule group.
			}
		}

		if ( $group_matches ) {
			return true; // This group matched, so we can return true immediately.
			}
	}

	return false; // No rule groups matched.
}
