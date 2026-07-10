export default {
  url: '/home.php?page=item_master_file',
  refSelector: '#it-ref',
  refPrefix: 'ITM-',
  fields: [
    { selector: '#it-name',        type: 'text',   value: 'TEST-Playwright-Item',                      column: 'name' },
    { selector: '#it-category',    type: 'select', value: 'materials',                                 column: 'category' },
    { selector: '#it-unit',        type: 'text',   value: 'pcs',                                        column: 'unit' },
    { selector: '#it-status',      type: 'select', value: 'active',                                    column: 'status' },
    { selector: '#it-description', type: 'text',   value: 'Playwright test item — safe to delete',      column: 'description' },
  ],
  dbVerify: {
    table: 'm_items',
    column: 'name',
    value: 'TEST-Playwright-Item',
  },
}
