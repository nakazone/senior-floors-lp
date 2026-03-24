/**
 * Google Sheets → Senior Floors CRM (sem Zapier)
 *
 * 1. Na planilha onde o Meta Instant Form grava leads, adicione uma coluna "CRM_Synced"
 *    (ou ajuste SYNC_COLUMN_HEADER abaixo).
 * 2. Abra Extensões → Apps Script, cole este arquivo, preencha CONFIG.
 * 3. Em Railway, defina SHEETS_SYNC_SECRET e use o mesmo valor em API_SYNC_SECRET.
 * 4. Salve o projeto, rode syncMetaLeadsToCrm() uma vez (Autorizar), depois crie gatilho:
 *    Relógio → a cada 5–15 minutos → syncMetaLeadsToCrm
 *
 * Cabeçalhos do Meta costumam variar; o script tenta vários nomes (case-insensitive).
 */

var CONFIG = {
  /** URL base do sistema no Railway (sem barra final) */
  API_BASE: 'https://senior-floors-system-production.up.railway.app',
  /**
   * Preferir: Apps Script → ⚙ Projeto → Propriedades do script → API_SYNC_SECRET
   * (mesmo valor que SHEETS_SYNC_SECRET no Railway). Fallback só para testes locais.
   */
  API_SYNC_SECRET: '',
  /** Nome exato da coluna que marca linha já enviada ao CRM */
  SYNC_COLUMN_HEADER: 'CRM_Synced',
  /** form-name enviado ao CRM (vira source Meta-Instant no backend) */
  FORM_NAME: 'meta-instant-form',
  /** Linha do cabeçalho (1 = primeira linha) */
  HEADER_ROW: 1,
};

function getApiSyncSecret_() {
  var fromProps = PropertiesService.getScriptProperties().getProperty('API_SYNC_SECRET');
  if (fromProps && String(fromProps).trim()) return String(fromProps).trim();
  if (CONFIG.API_SYNC_SECRET && String(CONFIG.API_SYNC_SECRET).trim()) return String(CONFIG.API_SYNC_SECRET).trim();
  throw new Error('Defina a propriedade do script API_SYNC_SECRET (ou CONFIG.API_SYNC_SECRET).');
}

function syncMetaLeadsToCrm() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var data = sheet.getDataRange().getValues();
  if (data.length < CONFIG.HEADER_ROW + 1) return;

  var headers = data[CONFIG.HEADER_ROW - 1].map(function (h) {
    return String(h || '').trim().toLowerCase();
  });
  var col = {
    name: findCol(headers, ['full name', 'name', 'nome', 'first name']),
    email: findCol(headers, ['email', 'e-mail', 'email address']),
    phone: findCol(headers, ['phone', 'phone number', 'mobile', 'telefone', 'tel']),
    zip: findCol(headers, ['zip', 'zip code', 'postal code', 'postcode', 'cep']),
    synced: findCol(headers, [CONFIG.SYNC_COLUMN_HEADER.toLowerCase(), 'crm synced', 'synced']),
  };
  if (col.synced < 0) {
    throw new Error('Coluna "' + CONFIG.SYNC_COLUMN_HEADER + '" não encontrada. Adicione na primeira linha.');
  }
  if (col.name < 0 || col.email < 0 || col.phone < 0 || col.zip < 0) {
    throw new Error('Faltam colunas (name/email/phone/zip). Cabeçalhos atuais: ' + headers.join(' | '));
  }

  var url = CONFIG.API_BASE.replace(/\/$/, '') + '/api/receive-lead';
  var synced = 0;

  for (var r = CONFIG.HEADER_ROW; r < data.length; r++) {
    var row = data[r];
    var flag = String(row[col.synced] || '').trim().toLowerCase();
    if (flag === 'true' || flag === 'yes' || flag === '1' || flag === 'ok' || flag === 'synced') continue;

    var name = String(row[col.name] || '').trim();
    var email = String(row[col.email] || '').trim();
    var phone = String(row[col.phone] || '').trim();
    var zipRaw = String(row[col.zip] || '').trim();
    if (!name || !email || !phone || !zipRaw) continue;

    var payload = {
      name: name,
      email: email,
      phone: phone,
      zipcode: zipRaw,
      'form-name': CONFIG.FORM_NAME,
    };

    var options = {
      method: 'post',
      contentType: 'application/x-www-form-urlencoded',
      payload: payload,
      headers: {
        'X-Sheets-Sync': '1',
        'X-Sheets-Sync-Secret': getApiSyncSecret_(),
      },
      muteHttpExceptions: true,
    };

    var res = UrlFetchApp.fetch(url, options);
    var code = res.getResponseCode();
    var body = res.getContentText() || '';

    if (code >= 200 && code < 300) {
      sheet.getRange(r + 1, col.synced + 1).setValue(new Date().toISOString());
      synced++;
    } else {
      // Não marca como synced para poder tentar de novo no próximo gatilho
      Logger.log('Falha linha ' + (r + 1) + ' HTTP ' + code + ' ' + body);
    }
  }

  Logger.log('syncMetaLeadsToCrm: enviadas ' + synced + ' linha(s).');
}

function findCol(headers, candidates) {
  for (var i = 0; i < candidates.length; i++) {
    var want = candidates[i].toLowerCase();
    for (var j = 0; j < headers.length; j++) {
      if (headers[j] === want) return j;
    }
  }
  for (var c = 0; c < candidates.length; c++) {
    var w = candidates[c].toLowerCase();
    for (var k = 0; k < headers.length; k++) {
      if (headers[k].indexOf(w) !== -1) return k;
    }
  }
  return -1;
}
