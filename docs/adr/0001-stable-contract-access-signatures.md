# Stable Contract Access Signatures

Status: accepted

Public contract access uses a stable signature derived from reservation data with a server secret, while the database stores only a hash of that signature. This lets existing reservations produce share links after backfill without storing raw bearer secrets, and it replaces Laravel temporary signed contract URLs because contract availability is controlled by the Reservation's current Contract Copy Expiration.
