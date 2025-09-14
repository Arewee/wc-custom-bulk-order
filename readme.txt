=== WC Custom Bulk Order ===
Contributors: gemini, richardviitanen
Tags: woocommerce, bulk, product, options, matrix
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ett skräddarsytt plugin för bulk-beställningar i WooCommerce med anpassade fält, prispåslag och kvantitetsrabatter.

== Description ==

Detta plugin möjliggör en produktmatris för variabla produkter, anpassade fält per orderrad, dynamisk prissättning och kvantitetsrabatter.

== Installation ==

1. Ladda upp `wc-custom-bulk-order`-mappen till `/wp-content/plugins/`-mappen.
2. Aktivera pluginet genom 'Plugins'-menyn i WordPress.
3. Konfigurera fält via ACF och inställningar på produktsidan.

== Kompatibilitet ==

Detta plugin är designat för att ersätta standardgränssnittet för **Variabla Produkter** för att visa en bulk-beställningsmatris. Det är inte avsett att användas på Enkla Produkter.

**Potentiella konflikter:**
Det finns en hög risk för konflikter om du försöker använda detta plugin på samma produkt som ett annat plugin som också modifierar köp-gränssnittet (t.ex. "Advanced Product Fields for WooCommerce" eller liknande). Båda pluginen kommer att konkurrera om att kontrollera visningen och prislogiken, vilket kan leda till ett trasigt gränssnitt eller felaktig prissättning.

**Rekommenderat arbetsflöde:**
Du kan säkert köra detta plugin och andra fält-plugins på samma webbplats, så länge de inte är aktiva på samma produkter.

*   **För Bulk-matris beställningar:** Använd en **Variabel Produkt**. Detta plugin aktiveras då automatiskt. Tilldela inte fält från andra formulär-plugins till denna produkt.
*   **För anpassade fält (t.ex. text, filuppladdningar):** Använd en **Enkel Produkt** och konfigurera den med ditt andra plugin (som "Advanced Product Fields"). Detta plugin kommer inte att störa.

Genom att separera användningsfallen per produkttyp kan du utnyttja styrkorna hos båda pluginen i din butik utan konflikter.

== Changelog ==

= 1.0.0 =
* Initial release.
