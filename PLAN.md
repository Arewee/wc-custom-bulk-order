# Plan för WC Custom Bulk Order - Version 2.0

Detta dokument beskriver planen för en större refaktorering och funktionsutökning av pluginet. Målet är att skapa en enhetlig, robust och flexibel lösning som hanterar anpassade fält för alla produkttyper, samtidigt som bulk-funktionaliteten bevaras för variabla produkter.

---

### Fas 1: Arkitektonisk Refaktorering (Grund & Stabilitet)

**Mål:** Skapa en ren arkitektur med tydliga ansvarsområden för att eliminera konflikter och stödja både enkla och variabla produkter.

**Steg 1.1: Återskapa `WC_CBO_ACF_Integration`**
*   **Ansvar:** Rendera ACF-fält på alla produkttyper, med respekt för platsregler.
*   **Potentiell Risk:** Temat skulle teoretiskt kunna sakna den standard-hook (`woocommerce_before_add_to_cart_form`) som används.
*   **Beredskap:** Risken är extremt låg. Vi har redan verifierat att hooken körs korrekt i den aktuella miljön. Steget bedöms som mycket säkert.

**Steg 1.2: Refaktorera `WC_CBO_Product_Matrix`**
*   **Ansvar:** Rendera bulk-matrisen (endast för variabla produkter).
*   **Logik:** Kommer att använda "Output Buffer"-metoden för att på ett robust sätt ersätta standardvyn utan att förlita sig på `remove_action`.
*   **Potentiell Risk:** En annan plugin/tema skulle kunna använda en output buffer på samma ställe och skapa en konflikt.
*   **Beredskap:** Implementationen kommer att innehålla skyddskod som kontrollerar om en buffer redan är aktiv. Genom att använda hook-prioriteter minimeras även tidsfönstret för en potentiell krock.

**Steg 1.3: Fixa bugg för val av "Bulk-attribut"**
*   **Ansvar:** Se till att inställningen "Bulk-attribut" i admin faktiskt används på framsidan.
*   **Potentiell Risk:** Låg. En användare skulle kunna spara ett ogiltigt värde.
*   **Beredskap:** Koden kommer att validera att det sparade värdet är ett giltigt attribut på produkten innan det används. Om ogiltigt, faller koden tillbaka på att auto-detektera, vilket förhindrar fel.

**Steg 1.4: Uppdatera `WC_CBO_Main`**
*   **Ansvar:** Ladda och initiera de två uppdaterade klasserna.
*   **Potentiell Risk:** Inga signifikanta risker. Detta är en okomplicerad ändring.
*   **Beredskap:** Standard-implementation.

---

### Fas 2: Implementation av Funktionskrav (Pris & Filuppladdning)

**Mål:** Implementera de efterfrågade funktionerna för prispåslag och filuppladdning på ett korrekt och säkert sätt.

**Steg 2.1: Implementera Prispåslag i Varukorgen**
*   **Ansvar:** Justera priset i varukorgen baserat på val i ACF-fält.
*   **Potentiell Risk:** Komplex interaktion med WooCommerce pris- och momsberäkningar.
*   **Beredskap:** Korrekt hook (`woocommerce_before_calculate_totals`) och prioritet kommer att användas för att säkerställa att påslagen appliceras innan WooCommerce slutgiltiga summering, vilket minimerar risken för felräkningar.

**Steg 2.2: Implementera Filuppladdning (Högst risk)**
*   **Ansvar:** Möjliggöra uppladdning av filer (logotyper) via ett ACF-fält.
*   **Potentiell Risk:** AJAX-filuppladdningar är komplext. Risker inkluderar server-konfiguration, filrättigheter och säkerhet.
*   **Beredskap:**
    1.  **Säkerhet:** All filhantering kommer att ske via WordPress inbyggda och säkra funktion `wp_handle_upload`.
    2.  **Stabilitet:** JavaScriptet kommer att använda den moderna `FormData`-standarden.
    3.  **Felhantering:** Tydlig felhantering kommer att byggas in för att meddela användaren om något går fel (t.ex. fel filformat) istället för att krascha.

**Steg 2.3: Slutgiltig Verifiering av Rabattlogik**
*   **Ansvar:** Säkerställa att volymrabatten beräknas på det slutgiltiga priset (inklusive prispåslag).
*   **Potentiell Risk:** Beräkningen sker i fel ordning.
*   **Beredskap:** Genom att använda olika prioriteter på samma hook (`woocommerce_before_calculate_totals`) tvingar vi fram rätt ordning: prispåslag körs på prioritet 10, volymrabatt på prioritet 20.

---

### Fas 3: Slutförande

**Steg 3.1: Kodgranskning & Testning**
*   En slutgiltig granskning av all ny kod och grundlig testning av alla funktioner och scenarion.

**Steg 3.2: Versionshantering**
*   Pluginet tilldelas version **2.0.0**.
*   `readme.txt` uppdateras med en komplett ändringslogg.
*   Koden committas och pushas till GitHub.