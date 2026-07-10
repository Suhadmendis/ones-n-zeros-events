// js/supabase-client.js — Supabase JS client, available globally as window.db
// URL and key are injected by partials/head.php from server/config.php

const { createClient } = supabase;

const db = createClient(window.__SUPABASE_URL__, window.__SUPABASE_KEY__);
