# Stock keeping: business rules

The warehouse tracks the stock of every product separately. A product is identified by its SKU.

- **R1.** A SKU looks like `ABC-1234`: three capital letters, a dash and four digits.
- **R2.** The quantity on hand is a whole number and is never negative.
- **R3.** Goods reserved for orders never exceed the quantity on hand.
- **R4.** The available quantity is the quantity on hand minus the reserved quantity.
- **R5.** Receiving goods adds a positive quantity to the stock on hand. Reservations stay as they are.
- **R6.** An order can reserve a positive quantity only when that much is available.
- **R7.** When an order is cancelled, its reservation is released. Only reserved goods can be released.
- **R8.** Shipping an order takes the reserved goods out of the warehouse: they leave both the stock on hand and the
  reservations. Only reserved goods can be shipped.
- **R9.** Staff record stock counts: the counted quantity, zero or more, replaces the quantity on hand. A count below
  the reserved quantity is rejected with the message "Count is below the reserved quantity" and nothing changes.

**Example.** Receive 10 items of `ABC-1234`, reserve 4 and ship 3: 7 are on hand, 1 is reserved and 6 are
available.
