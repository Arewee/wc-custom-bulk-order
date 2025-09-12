### Slutgiltig Utvecklingsplan (v1.3)

Detta dokument beskriver den överenskomna planen för utvecklingen av `wc-custom-bulk-order`-pluginet.

---

**Fas 1: Projekt-setup och grundstruktur**
*   Skapa en ren, standardiserad och skalbar filstruktur för pluginet, inklusive huvudfil, klasser och mappar för assets, includes, admin och public.

**Fas 2: Administratörsgränssnitt**
1.  **Prissättning i ACF:** Implementera den specialkod som krävs för att administratören ska kunna ange ett prispåslag direkt i ACF:s gränssnitt för val-baserade fält (radioknappar, dropdowns).
2.  **Anpassad produktdata-panel:** Bygga en ny panel på produktens redigeringssida ("Bulk-inställningar") för att hantera:
    *   **Rabattstege:** Gränssnitt för att definiera nivåer för kvantitetsrabatt (Från antal, Till antal, Rabatt i %).
    *   **Minsta totala antal:** Fält för att ange minsta antal produkter som krävs för en beställning.
    *   **Produktionstid:** Fält för att ange produktionstiden i dagar.

**Fas 3: Frontend - Den interaktiva produktmatrisen**
1.  **UI & Realtidsberäkning (JavaScript):** Utveckla ett skript som hanterar all realtidslogik i kundens webbläsare.
    *   Visa produktmatrisen och de anpassade fälten.
    *   Realtidsuppdatera en "Sammanfattning" som visar totalt antal, rabatt, prispåslag, slutgiltigt totalpris och beräknat leveransdatum.
    *   Aktivera/inaktivera "Lägg till i varukorg"-knappen baserat på om minimiantalet är uppnått.

**Fas 4: Varukorg & Orderhantering**
1.  **Lägg till i varukorg:** Via AJAX, lägg varje rad från matrisen som en separat produkt i varukorgen med det slutgiltiga, färdigberäknade priset.
2.  **Visa i varukorg/kassa/order:** Säkerställa att all anpassad data (text, bildlänk, prispåverkande tillval, rabatt) visas tydligt genom hela köpflödet för både kund och administratör.

**Fas 5: GDPR & Bildhantering**
*   Säkerställa att uppladdade bilder sparas i en skyddad mapp utanför mediabiblioteket och raderas automatiskt när en order markeras som "Slutförd".

**Fas 6: Kvalitetssäkring & Dokumentation**
*   Genomföra kodgranskning, säkerställa kodstil (WordPress Coding Standards), prestanda och skriva grundläggande dokumentation (`readme.txt`).

**Fas 7: Framtida Förbättringar (Planerad för v2.0)**
1.  **Live Förhandsgranskning:**
    *   **Admin:** Utöka "Bulk-inställningar" med möjlighet att ladda upp en bas-bild och definiera en "tryckyta".
    *   **Frontend:** När kunden skriver text, rendera denna i realtid ovanpå en bild av produkten för omedelbar visuell feedback.
