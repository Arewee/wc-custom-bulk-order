# Coding Standards & Granskningsprocess

Detta dokument definierar de regler och den process för kvalitetssäkring som ska följas under utvecklingen av detta plugin.

---

Innan någon kod delas eller committas, ska följande kontroller och korrigeringar alltid genomföras. En kort sammanfattning av vad som har identifierats och korrigerats ska presenteras.

- **Syntax:** Kontrollera och rätta syntaxfel.

- **Kodkvalitet:** Sök och korrigera: obsolet kod, duplicerad kod, felaktig hårdkodad data och oanvända kodfragment. Om hårdkodad data eller demo-data behöver användas, var tydlig med att kommentera det och var i koden det används.

- **Variabler:** Kontrollera att variabler har korrekt datatype, unika namn och rimliga värden.

- **Struktur:** Granska att varje funktion, metod och klass är lagom kort, har ett tydligt syfte och använder relevant logik.

- **Logik:** Verifiera att logiken är rationell, att alla kodgrenar hanteras och att programflödet är konsekvent.

- **Best Practice:** Se till att koden följer best practice för:
    - **Säkerhet:** Skydd mot injektioner, hantering av känslig data, korrekta åtkomstkontroller.
    - **Prestanda:** Undvik onödig resursanvändning, optimera algoritmer, minimera latens.
    - **Anvä1. hndarvänlighet:** Tydliga felmeddelanden, enkla gränssnitt, läsbar kod. Använd BEM för CSS-klasser och variabler.

- **Kodstil:** Kontrollera kodstil: indentering, formatering och namngivning enligt etablerad WordPress-standard.

- **Beroenden:** Identifiera och dokumentera eventuella beroenden (externa bibliotek, API-anrop), kontrollera kompatibilitet.

- **Versionshantering:** Tilldela ett nytt versionsnummer vid varje ' kodändring för spårbarhet och historik.

- **Förbättringar:** Föreslå eventuella ytterligare förbättringar som upptäcks vid granskningen.
