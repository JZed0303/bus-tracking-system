export const shortTime = (value) => {
  try {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
};

export const formatTime = (value) => {
  try {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value ?? '');
    return d.toLocaleString();
  } catch {
    return String(value ?? '');
  }
};

// NOTE: you now have bus_code (not bus_number)
export const getBusNumberLabel = (t) => {
  if (!t) return null;

  if (t.bus_id != null && String(t.bus_id).trim() !== '') return `Bus #${t.bus_id}`;
  return null;
};

export const formatBusDisplayName = (t) => {
  if (!t) return '—';

  const parts = [];

  // If you later add a bus "name", you can push it here.
  // if (t.bus_name && String(t.bus_name).trim() !== '') parts.push(String(t.bus_name).trim());

  // show plate and model (recommended to show in title)


  const busNo = getBusNumberLabel(t);
  if (busNo) parts.push(busNo);

  return parts.length ? parts.join(' • ') : (busNo || 'Bus');
};

export const formatBusMetaLine = (t) => {
  if (!t) return '—';

  const parts = [];
  if (t.plate_number && String(t.plate_number).trim() !== '') parts.push(String(t.plate_number).trim());
  if (t.brand_model && String(t.brand_model).trim() !== '') parts.push(String(t.brand_model).trim());

  return parts.length ? parts.join(' • ') : '—';
};
