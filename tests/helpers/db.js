import { createClient } from '@supabase/supabase-js'
import { expect } from '@playwright/test'
import ws from 'ws'

const supabase = createClient(
  process.env.SUPABASE_URL,
  process.env.SUPABASE_SERVICE_KEY,
  { realtime: { transport: ws } },
)

export async function assertRecord({ table, column, value }, fields = []) {
  const { data, error } = await supabase.from(table).select('*').eq(column, value).limit(1)
  if (error) throw new Error(`DB query failed: ${error.message}`)
  if (!data.length) throw new Error(`DB record not found: ${table}.${column} = ${value}`)

  const row = data[0]
  for (const field of fields) {
    expect(String(row[field.column] ?? ''), `${table}.${field.column}`).toBe(String(field.value))
  }
}

export async function deleteRecord({ table, column, value }) {
  const { error } = await supabase.from(table).delete().eq(column, value)
  if (error) throw new Error(`DB delete failed: ${error.message}`)
}

// For tests whose form depends on a pre-existing row in another master table
// (e.g. an FK dropdown) — inserts directly, bypassing the app's ref-generation flow.
export async function seedRecord(table, fields) {
  const { data, error } = await supabase.from(table).insert(fields).select().single()
  if (error) throw new Error(`DB insert failed: ${error.message}`)
  return data
}

// For tests whose FK dropdown should reuse existing real data (e.g. a fleet
// vehicle) rather than seeding and deleting a row that isn't the test's own.
export async function getAnyRecord(table) {
  const { data, error } = await supabase.from(table).select('*').limit(1).single()
  if (error) throw new Error(`DB query failed: ${error.message}`)
  return data
}

// For EAV-style settings tables (one row per key, e.g. sys_company_settings)
// rather than one-row-per-record tables. Each field needs a `settingKey`.
export async function assertSettings(fields, table = 'sys_company_settings') {
  for (const field of fields) {
    const { data, error } = await supabase
      .from(table)
      .select('setting_value')
      .eq('setting_key', field.settingKey)
      .limit(1)
    if (error) throw new Error(`DB query failed: ${error.message}`)
    if (!data.length) throw new Error(`Setting not found: ${table}.setting_key = ${field.settingKey}`)
    expect(data[0].setting_value, `${table}.${field.settingKey}`).toBe(String(field.value))
  }
}
