/**
 * Valida se um CEP (ZIP) dos EUA está dentro do raio de 50 milhas de Denver, CO.
 * Usa api.zippopotam.us (gratuita, sem chave) para obter lat/lon do ZIP.
 */

const DENVER_LAT = 39.7392;
const DENVER_LON = -104.9903;
const RADIUS_MILES = 50;

function haversineMiles(lat1, lon1, lat2, lon2) {
  const R = 3959; // Earth radius in miles
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

/**
 * @param {string} zip - ZIP code (apenas dígitos ou com hífen)
 * @returns {Promise<{ inRange: boolean, distanceMiles?: number, error?: string }>}
 */
export async function isZipWithin50MilesOfDenver(zip) {
  const clean = String(zip || '').replace(/\D/g, '').slice(0, 5);
  if (clean.length < 5) {
    return { inRange: false, error: 'Invalid zip code' };
  }
  try {
    const res = await fetch(`https://api.zippopotam.us/us/${clean}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) {
      return { inRange: false, error: 'Zip code not found' };
    }
    const data = await res.json();
    const place = data.places && data.places[0];
    if (!place || place.latitude == null || place.longitude == null) {
      return { inRange: false, error: 'Location not found for zip' };
    }
    const lat = Number(place.latitude);
    const lon = Number(place.longitude);
    const distanceMiles = haversineMiles(DENVER_LAT, DENVER_LON, lat, lon);
    return {
      inRange: distanceMiles <= RADIUS_MILES,
      distanceMiles: Math.round(distanceMiles * 10) / 10,
    };
  } catch (e) {
    return { inRange: false, error: e.message || 'Lookup failed' };
  }
}
