# Riho Domain

This context describes the travel reservation language used by Riho. It keeps domain terms precise so contract and reservation behavior stays consistent across the product.

## Language

**Contract Copy**:
The current rendered contract document for one Reservation. A Reservation has at most one current Contract Copy, and sharing the contract does not create a historical contract version.
_Avoid_: Generated contract version, contract archive entry

**Shared Contract Link**:
A public reference that allows a recipient with the Contract Access Signature to open the current Contract Copy for a Reservation while the Contract Copy has not expired. Many Shared Contract Links may exist for one Reservation, but they all open the same current Contract Copy and do not preserve older Contract Copies.
_Avoid_: Contract version link, permanent public contract URL

**Contract Access Signature**:
A stable secret value assigned to a Reservation and required to open its Contract Copy without authentication. The application stores proof of the value, not the raw secret; it is not the visual signature printed on the contract and it is not a generated document version.
_Avoid_: Signature image, generated contract signature, version signature

**Contract Share Action**:
An intentional user action that exposes a Shared Contract Link outside the authenticated app. The main send-contract action, email, Viber, WhatsApp, and copying the contract link are Contract Share Actions; internal preview, internal download, and opening the reservation edit page are not. A Contract Share Action may reuse an unexpired Contract Copy instead of rendering a new document.
_Avoid_: Contract preview, contract download, page load

**Contract Copy Expiration**:
The point in time after which the current Contract Copy should no longer be kept. A Contract Share Action may extend it; once it expires, the stored document is eligible for removal and can only be recreated by another Contract Share Action.
_Avoid_: Generated timestamp, share timestamp

**Expired Contract Cleanup**:
Removal of temporary Contract Copy access material after Contract Copy Expiration. It removes the stored document and clears the Reservation's contract-copy reference, but it never deletes the Reservation.
_Avoid_: Reservation cleanup, expired reservation deletion

**Contract Copy Invalidation**:
Removal of the current Contract Copy because Reservation data changed. It keeps the Reservation and Contract Access Signature intact, and the next Contract Share Action prepares a new Contract Copy.
_Avoid_: Signature reset, reservation reset

## Example Dialogue

Developer: "If I open a Shared Contract Link from last week, should it show last week's contract?"

Domain expert: "No. If the Contract Copy has not expired, it opens the Reservation's current Contract Copy."

Developer: "So sharing the contract again can reuse the current Contract Copy, and old valid links still point to that same copy?"

Domain expert: "Yes."

Developer: "Can one Reservation have many Shared Contract Links?"

Domain expert: "Yes, but they all point to one current Contract Copy."

Developer: "What proves that an unauthenticated visitor may open the Contract Copy?"

Domain expert: "They must have the Reservation's Contract Access Signature."

Developer: "Should previewing the contract inside Riho refresh the Contract Copy?"

Domain expert: "No. Refresh it only when the user performs a Contract Share Action."

Developer: "What does the main send-contract action do?"

Domain expert: "It prepares the Shared Contract Link and opens that public PDF link."

Developer: "Should Riho prepare a public contract link when the reservation edit page opens?"

Domain expert: "No. A Shared Contract Link is prepared only during a Contract Share Action."

Developer: "What does the timestamp on a Contract Copy mean?"

Domain expert: "It is the Contract Copy Expiration, not the time the document was generated."

Developer: "Does Expired Contract Cleanup ever delete a Reservation?"

Domain expert: "Never. It only removes the temporary Contract Copy material."

Developer: "What happens to a shared Contract Copy when the Reservation changes?"

Domain expert: "Riho invalidates the Contract Copy, but keeps the Reservation and Contract Access Signature."

Developer: "Can the Shared Contract Link and Contract Copy expire at different times?"

Domain expert: "The link carries the Contract Access Signature, while the Reservation's Contract Copy Expiration controls access and retention."

Developer: "If the stored Contract Copy is gone but a recipient still has the Shared Contract Link, should the link rebuild it?"

Domain expert: "No. The link fails because opening a Shared Contract Link is read-only."
