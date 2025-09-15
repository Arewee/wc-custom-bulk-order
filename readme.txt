=== WC Custom Bulk Order ===
Contributors: gemini, richardviitanen
Tags: woocommerce, bulk, product, options, matrix
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ett skräddarsytt plugin för bulk-beställningar i WooCommerce med anpassade fält, prispåslag och kvantitetsrabatter.

== Description ==

Detta plugin möjliggör en produktmatris för variabla produkter, anpassade fält per orderrad, dynamisk prissättning och kvantitetsrabatter.

== Installation ==

1. Ladda upp `wc-custom-bulk-order`-mappen till `/wp-content/plugins/`-mappen.
2. Aktivera pluginet genom 'Plugins'-menyn i WordPress.
3. Konfigurera fält via ACF och inställningar på produktsidan.

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
