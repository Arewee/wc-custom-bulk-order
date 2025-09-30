=== WC Custom Bulk Order ===
Contributors: gemini, richardviitanen
Tags: woocommerce, bulk, product, options, matrix
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ett skräddarsytt plugin för bulk-beställningar i WooCommerce med anpassade fält, prispåslag och kvantitetsrabatter.

== Description ==

Detta plugin möjliggör en produktmatris för variabla produkter, anpassade fält per orderrad, dynamisk prissättning och kvantitetsrabatter.

== Installation ==

1. Ladda upp `wc-custom-bulk-order`-mappen till `/wp-content/plugins/`-mappen.
2. Aktivera pluginet genom 'Plugins'-menyn i WordPress.
3. Konfigurera fält via ACF och inställningar på produktsidan.

== Developer Resources ==

For developers contributing to this project, please refer to the `DEVELOPER_NOTES.md` file in the root directory. It contains important links to the official documentation for WordPress, WooCommerce, ACF, and other key tools used in this environment. Use these resources to ensure code is compliant with the latest standards and best practices.

== Architectural Notes ==

Detta plugin använder medvetet egen, anpassad kod för att rendera fält och hantera interaktiv logik, istället för att förlita sig på en sidbyggare som Elementor. Detta arkitektoniska val gjordes för att säkerställa:
*   **Maximal Prestanda:** Genom att endast ladda den nödvändiga koden, utan extra "bloat" från en sidbyggare.
*   **Full Kontroll:** Komplex logik för dynamisk prissättning, validering och interaktivitet kräver en detaljkontroll som sidbyggare inte erbjuder.
*   **Oberoende:** Pluginet fungerar med vilket tema eller sidbyggare som helst, eftersom det inte har några beroenden till dem.

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

= 2.0.0 =
* **Stor Arkitektonisk Refaktorering:** Hela pluginet har fått en ny, mer robust och underhållbar arkitektur.
* **Ny Funktion: Universella ACF-fält:** Anpassade fält (ACF) stöds nu på alla produkttyper, inte bara variabla produkter.
* **Ny Funktion: Prispåslag:** Stöd för prispåslag baserat på val i ACF-fält (t.ex. radioknappar, kryssrutor).
* **Ny Funktion: AJAX Filuppladdning:** Implementerat ett säkert, AJAX-baserat system för filuppladdning i ACF-fält, komplett med UI-feedback.
* **Buggfix: Val av Bulk-attribut:** Inställningen "Attribut för Antalsmatris" i adminpanelen används nu korrekt för att bygga produktmatrisen.
* **Förbättring: Robust Matris-rendering:** Bytt ut den tidigare `remove_action`-metoden mot en stabilare "Output Buffer"-metod för att rendera bulk-matrisen.
* **Förbättring: Rabattberäkning:** Volymrabatten beräknas nu korrekt på det slutgiltiga priset, inklusive eventuella prispåslag från ACF-fält.

= 1.8.0 =
* **FEAT:** Volume discount ladder is now displayed on the product page.
* **FIX:** ACF field data is now correctly saved to the cart and displayed on cart/checkout pages. A CSS class mismatch between the PHP renderer and the JavaScript handler was corrected.
* **FIX:** The volume discount is now correctly applied to the cart total. The logic was refactored to use the standard `WC_Cart::add_fee()` method instead of attempting to manipulate line item prices.
* **REFACTOR:** Consolidated all front-end rendering (ACF fields, product matrix, discount ladder) into the `WC_CBO_Product_Matrix` class, removing the conflicting `WC_CBO_ACF_Integration` class and resolving a silent fatal error.

= 1.7.0 =
* **FIX:** A critical and multi-layered bug in the ACF location rule evaluation has been fixed. The custom helper function now correctly evaluates all rule types (Post, Post Taxonomy, etc.) and respects the 'active' status of field groups. This ensures that fields displayed on the front-end perfectly match the active rules configured in the ACF admin panel.
* **REFACTOR:** The field rendering logic was simplified to use `acf_render_field_wrap()`, removing complex, faulty code and correctly displaying empty fields for user input.
* **VERSION:** Consolidated plugin version to 1.7.0 after a complex debugging and bugfix cycle.

= 1.5.1 =
* **FIX:** Corrected a critical bug where ACF fields would not render on variable product pages. The issue was traced to a faulty and non-existent ACF API call (`acf_match_location_rules`).
* **REFACTOR:** The field rendering logic now uses the correct `acf_get_field_groups()` API call, making it robust and compliant with ACF best practices.
* **REFACTOR:** Plugin initialization now uses the standard `plugins_loaded` hook to prevent fatal errors.
* **DOCS:** Added `DEVELOPER_NOTES.md` with links to official documentation for key project technologies.

= 1.4.2 =
* **FIX:** Corrected a critical bug in the JavaScript that prevented ACF field values (like "Tryckfärg") from being saved to the cart. The script now correctly identifies and reads values from all field types.
* **REFACTOR:** The JavaScript for handling ACF fields has been refactored for improved clarity, robustness, and maintainability.

= 1.4.1 =
* **FIX:** Ensure empty ACF fields are not displayed in the cart, preventing labels from showing without values.
* **FIX:** Unified ACF field handling in JavaScript to ensure consistent price calculation and cart data.

= 1.4.0 =
* **FIX:** The product matrix now correctly identifies and displays global variation attributes (e.g., "Size") instead of just the first available attribute. This fixes a bug where variations would not appear if multiple attributes were present.

= 1.3.1 =
* **ENHANCEMENT:** Visar nu endast attributets namn (t.ex. "M") i storlekstabellen istället för hela produktnamnet.

= 1.3.0 =
* **CORRECTION REFACTOR:** Hela layouten har byggts om för att matcha korrekt arbetsflöde.
* **FIX:** ACF-fält (t.ex. "Tryckfärg") visas nu som globala val ovanför en enklare tabell.
* **FIX:** Tabellen visar nu endast produktvariationer (t.ex. "Storlek") med ett antal-fält per rad.
* **FIX:** Prisberäkning och varukorgslogik har anpassats till den nya, korrekta layouten.

= 1.2.0 =
* **MAJOR REFACTOR:** Hela produktmatrisen har byggts om från grunden.
* **FIX:** Layouten är nu en korrekt 2D-matris som använder produktattribut för rader och ACF-fält (som "Tryckfärg") för kolumner.
* **FIX:** Prispåslag från ACF-fält (t.ex. "Guld:50") beräknas nu korrekt i realtid.
* **FIX:** Den överflödiga, vanliga WooCommerce-väljaren för variationer har tagits bort.
* **ENHANCEMENT:** Etiketter för ACF-prisfält formateras nu för att vara mer läsbara (t.ex. "Guld (+50,00 kr)").

= 1.0.0 =
* Initial release.