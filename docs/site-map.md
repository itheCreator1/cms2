# Χάρτης ιστότοπου / Site map

How the old eares.gr (Joomla) maps onto the new WordPress site. `scripts/setup-content.php` creates the pages and categories; the menu lives in the theme (`eares_theme_menu_items()` in `wp-content/themes/eares/functions.php`).

| Old menu | New menu | URL | WordPress |
|---|---|---|---|
| Αρχή | Αρχή | `/` | Page "Αρχή" as the static front page, rendered by `templates/front-page.html` |
| Ε.Α.Ρ.Ε.Σ. | Η Ένωση | `/i-enosi/` | Page: history, board (pattern "Διοικητικό Συμβούλιο"), statutes |
| Νέα | Νέα | `/nea/` | Posts page; categories Νέα, Ανακοινώσεις, Εκδηλώσεις |
| Η εφημερίδα μας | Ο Ριζαρείτης | `/category/rizareitis/` | One post per issue in category "Ο Ριζαρείτης": front page as featured image, PDF in the text |
| Ρ.Ε.Σ. | Η Σχολή | `/rizareios-scholi/` | Page about the school |
| Συνδρομές - εισφορές | Συνδρομές | `/syndromes/` | Page |
| Επικοινωνία | Επικοινωνία | `/epikoinonia/` | Page |
| Είσοδος μελών (sidebar form) | "Είσοδος μελών" link in the header | `/wp-login.php` | Accounts are created by the office; self-registration stays off |
| Αναζήτηση (sidebar) | Search button in the header | `/?s=` | |
| Πρωτοσέλιδο (sidebar) | "Ο Ριζαρείτης" card on the front page | | Latest post in the category |

## Front page, top to bottom

1. Dome photo with the association's name.
2. Sticky posts as notices ("Καρφίτσωμα στην αρχική" in the post editor), e.g. elections.
3. Latest news (left); latest Ριζαρείτης front page and events (right).
4. The welcome text from the old site, with the church porch photo.
5. Membership band.

## For editors

- **Announcement at the top of the front page:** tick *Καρφίτσωμα στην αρχική* on the post; untick it when it's over.
- **New Ριζαρείτης issue:** new post in category *Ο Ριζαρείτης*, set the front page as the *featured image*, add the PDF with a *File* block.
- **Events:** posts in *Εκδηλώσεις*, with date and time in the text.
