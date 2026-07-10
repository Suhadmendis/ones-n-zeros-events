Update the Existing Quotation

Update the current existing quotation module to use the following common quotation structure.

Do not create a new quotation module. Update the existing one according to the current ERP standards and patterns.

Header Fields

* Quotation No.
* Revision No.
* Quotation Date
* Valid Until
* Customer
* Contact Person
* Subject / Title
* Customer Reference
* Currency
* Salesperson / Prepared By
* Price List
* Payment Terms
* Delivery / Completion Period
* Status
* Notes
* Terms & Conditions
* Internal Notes

Keep appropriate fields optional so the quotation can be saved without values that are not relevant to a particular company.

Quotation Lines Table

Add the following columns to the quotation form table:

#	Item / Service	Description	Image	Qty	Unit	Unit Price	Discount	Tax	Amount

The following line values should be optional where appropriate:

* Description
* Image
* Unit
* Discount
* Tax

Use the existing ERP standards, architecture, form patterns, database conventions, calculations, validation, and UI components when updating the module.