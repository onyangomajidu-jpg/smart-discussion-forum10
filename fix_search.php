<?php
$f = __DIR__ . '/laravel/resources/views/lecturer/topics.blade.php';
$txt = file_get_contents($f);
$lines = explode("\n", $txt);
$keywords = ['audio-play-btn','btn-edit','audio_path','chat-actions','btn-mic','audio-msg-bubble','btn-send-audio','previewAudio'];
foreach ($lines as $i => $l) {
    foreach ($keywords as $kw) {
        if (strpos($l, $kw) !== false) {
            echo ($i+1) . ': ' . trim($l) . "\n";
            break;
        }
    }
}
