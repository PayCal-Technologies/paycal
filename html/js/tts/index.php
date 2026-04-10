<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * TTS fragment included by /js/index.php.
 * Core script bootstraps auth/headers/config before this include runs.
 */
$user = User::current();
assert(null !== $user);

$voiceRate = 1.0;
$voicePitch = 1.0;
$voiceVolume = 1.0;

if (property_exists($user, 'voice_rate') && is_numeric((string) $user->voice_rate)) {
  $voiceRate = (float) $user->voice_rate;
}
if (property_exists($user, 'voice_pitch') && is_numeric((string) $user->voice_pitch)) {
  $voicePitch = (float) $user->voice_pitch;
}
if (property_exists($user, 'voice_volume') && is_numeric((string) $user->voice_volume)) {
  $voiceVolume = (float) $user->voice_volume;
}

$ttsCategories = [
  'status' => ['level' => 'all'],
  'navigation' => ['level' => 'all'],
  'confirmation' => ['level' => 'all'],
  'error' => ['level' => 'important', 'priority' => true, 'rate' => 1.1],
];

$ttsConfig = [
  'audio_feedback' => (string) ($user->audio_feedback ?? 'all'),
  'voice' => (string) ((property_exists($user, 'voice') && is_string($user->voice) && $user->voice !== '') ? $user->voice : 'system_default'),
  'voice_rate' => $voiceRate,
  'voice_pitch' => $voicePitch,
  'voice_volume' => $voiceVolume,
  'categories' => $ttsCategories,
];

?>
(() => {
  window.tts = Object.assign({
    audio_feedback: 'all',
    voice: 'system_default',
    voice_rate: 1.0,
    voice_pitch: 1.0,
    voice_volume: 1.0,
    categories: {},
  }, <?php echo json_encode($ttsConfig, JSON_UNESCAPED_SLASHES); ?>);

  const state = {
    queue: [],
    speaking: false,
    lastMessage: '',
    lastTime: 0,
    voices: [],
  };

  const levelAllowed = (category) => {
    const mode = String(window.tts.audio_feedback || 'all').toLowerCase();
    if (mode === 'off' || mode === 'none') return false;
    const cfg = window.tts.categories?.[category] || { level: 'all' };
    if (mode === 'all') return true;
    return String(cfg.level || 'all').toLowerCase() === 'important';
  };

  const normalizeEntry = (category, text) => ({
    category: String(category || 'status'),
    text: String(text || '').trim(),
    at: Date.now(),
  });

  const isDuplicate = (entry) => {
    if (entry.text === '') return true;
    if (entry.text === state.lastMessage && (entry.at - state.lastTime) < 1000) return true;
    return false;
  };

  const selectVoice = () => {
    const selected = String(window.tts.voice || 'system_default').toLowerCase();
    if (state.voices.length === 0) return null;

    const first = (fn) => state.voices.find(fn) || null;
    const nth = (fn, n) => {
      const all = state.voices.filter(fn);
      return all[n] || all[0] || null;
    };

    if (selected === 'system_default') return first(v => v.default) || state.voices[0];
    if (selected === 'system_female') return first(v => /female|samantha|victoria|karen|zira|aria|allison|ava|serena|joanna/i.test(v.name)) || first(v => /en-/i.test(v.lang)) || state.voices[0];
    if (selected === 'system_male') return first(v => /male|david|daniel|alex|fred|tom|jorge|diego|rishi|matthew/i.test(v.name)) || first(v => /en-/i.test(v.lang)) || state.voices[0];
    if (selected === 'google_en_us_1') return nth(v => /google/i.test(v.name) && /en-US/i.test(v.lang), 0) || nth(v => /en-US/i.test(v.lang), 0) || state.voices[0];
    if (selected === 'google_en_us_2') return nth(v => /google/i.test(v.name) && /en-US/i.test(v.lang), 1) || nth(v => /en-US/i.test(v.lang), 1) || state.voices[0];
    if (selected === 'google_en_ca_1') return nth(v => /google/i.test(v.name) && /en-CA/i.test(v.lang), 0) || nth(v => /en-CA/i.test(v.lang), 0) || nth(v => /en-US/i.test(v.lang), 0) || state.voices[0];

    return first(v => String(v.name).toLowerCase() === selected)
      || first(v => String(v.name).toLowerCase().includes(selected))
      || first(v => String(v.lang).toLowerCase().includes(selected))
      || first(v => v.default)
      || state.voices[0];
  };

  const processNext = () => {
    if (!('speechSynthesis' in window)) return;
    if (state.speaking || state.queue.length === 0) return;

    const entry = state.queue.shift();
    const categoryCfg = window.tts.categories?.[entry.category] || {};
    const utter = new SpeechSynthesisUtterance(entry.text);
    const voice = selectVoice();

    if (voice) {
      utter.voice = voice;
      utter.lang = voice.lang || utter.lang;
    }

    utter.rate = Number(categoryCfg.rate ?? window.tts.voice_rate ?? 1.0);
    utter.pitch = Number(categoryCfg.pitch ?? window.tts.voice_pitch ?? 1.0);
    utter.volume = Number(window.tts.voice_volume ?? 1.0);

    state.speaking = true;
    state.lastMessage = entry.text;
    state.lastTime = entry.at;

    utter.onend = () => {
      state.speaking = false;
      processNext();
    };
    utter.onerror = () => {
      state.speaking = false;
      processNext();
    };

    if (window.Lens && typeof window.Lens.event === 'function') {
      try {
        window.Lens.event('tts', { category: entry.category, text: entry.text });
      } catch {}
    }

    window.speechSynthesis.speak(utter);
  };

  const loadVoices = () => {
    if (!('speechSynthesis' in window)) return [];
    state.voices = window.speechSynthesis.getVoices() || [];
    return state.voices;
  };

  const enqueue = (category, text) => {
    if (!('speechSynthesis' in window)) return false;
    const entry = normalizeEntry(category, text);
    if (!levelAllowed(entry.category) || isDuplicate(entry)) return false;

    const categoryCfg = window.tts.categories?.[entry.category] || {};
    if (categoryCfg.priority === true) {
      window.speechSynthesis.cancel();
      state.queue = [entry, ...state.queue];
      state.speaking = false;
      processNext();
      return true;
    }

    state.queue.push(entry);
    processNext();
    return true;
  };

  const speakNow = (text) => {
    if (!('speechSynthesis' in window)) return false;
    window.speechSynthesis.cancel();
    state.speaking = false;
    state.queue.unshift(normalizeEntry('status', text));
    processNext();
    return true;
  };

  const stop = () => {
    if (!('speechSynthesis' in window)) return;
    state.queue = [];
    state.speaking = false;
    window.speechSynthesis.cancel();
  };

  window.TTS = {
    enqueue,
    speakNow,
    stop,
    loadVoices,
    setVoice: (name) => { window.tts.voice = String(name || 'system_default'); return window.tts.voice; },
    setRate: (rate) => { window.tts.voice_rate = Number(rate || 1.0); return window.tts.voice_rate; },
    setPitch: (pitch) => { window.tts.voice_pitch = Number(pitch || 1.0); return window.tts.voice_pitch; },
    setVolume: (volume) => { window.tts.voice_volume = Number(volume || 1.0); return window.tts.voice_volume; },
  };

  loadVoices();
  if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = loadVoices;
  }
})();
