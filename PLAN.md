### Slutgiltig Utvecklingsplan (v1.5)

Detta dokument beskriver den överenskomna planen för utvecklingen av `wc-custom-bulk-order`-pluginet.

---

**Fas 1: Projekt-setup och grundstruktur**
*   Skapa en ren, standardiserad och skalbar filstruktur för pluginet.

**Fas 2: Administratörsgränssnitt**
1.  **Prissättning i ACF:** Ge administratören möjlighet att ange prispåslag för val-baserade fält.
2.  **Anpassad produktdata-panel:** Bygga en "Bulk-inställningar"-panel på produktsidan för att hantera rabattstege, minimiantal och produktionstid.

**Fas 3: Frontend - Interaktiv produktmatris**
1.  **UI & Realtidsberäkning (JavaScript):** Utveckla ett skript som hanterar realtidsuppdatering av antal, priser, rabatter och leveransdatum.

**Fas 4: Varukorg & Orderhantering**
1.  **Lägg till i varukorg:** Lägg varje rad från matrisen som en separat, korrekt prissatt produkt i varukorgen via AJAX.
2.  **Visa anpassad data:** Säkerställa att all anpassad data visas genom hela köpflödet.

**Fas 5: GDPR & Bildhantering**
*   Säkerställa att uppladdade bilder hanteras och raderas korrekt.

**Fas 6: Dynamisk ACF-integration**
*   **Mål:** Låta icke-tekniker hantera visning av anpassade fält utan kod.
*   **Implementering:**
    1.  **Dynamisk Fältgruppsväljare:** En dropdown på produktsidan som, baserat på ACFs "Location Rules", endast visar relevanta fältgrupper att välja för frontend-visning.
    2.  **Automatisk Frontend Rendering:** En funktion som renderar den valda fältgruppen.
    3.  **Visuell Färgväljare:** Specialhantering för ACF "Radio Button"-fält för att visa klickbara färgrutor (swatches).

**Fas 7: Frontend-förbättringar (Nytt i v1.5)**
1.  **Visa Rabattstege:** Implementera en visuell representation av produktens rabattstege på produktsidan, så att kunden tydligt ser vilka kvantitetsnivåer som ger rabatt.

**Fas 8: Kvalitetssäkring & Finalisering**
1.  **Kodgranskning:** Säkerställa kodkvalitet och efterlevnad av WordPress Coding Standards.
2.  **Dokumentation:** Skriva grundläggande `readme.txt` och kommentera koden där det behövs.
3.  **CSS & UX-check (Nytt i v1.5):** Genomföra en övergripande granskning av pluginets styling och användarupplevelse för att säkerställa en polerad och professionell finish.

**Fas 9: Framtida Förbättringar (Planerad för v2.0)**
1.  **Delat Lagersaldo (Bill of Materials - BOM):** Utreda och eventuellt implementera stöd för att flera produkter kan dela lagersaldo från en gemensam komponentprodukt. (Funktion parkerad tills vidare).
2.  **Live Förhandsgranskning:** Ge kunden omedelbar visuell feedback på text-inmatning genom att rendera den på en produktbild i realtid.