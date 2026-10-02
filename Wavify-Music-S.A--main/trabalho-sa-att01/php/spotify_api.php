<?php
// ==========================================
// Manutenção 07: Integração com Spotify API
// Aqui colocaremos a lógica para autenticar e puxar dados do Spotify
// Substitua CLIENT_ID e CLIENT_SECRET com suas chaves!
// ==========================================

define('SPOTIFY_CLIENT_ID', 'SUA_CHAVE_AQUI');
define('SPOTIFY_CLIENT_SECRET', 'SEU_SECRET_AQUI');

function get_spotify_token() {
    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    $auth = base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic $auth",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if (!$result) return false;
    $data = json_decode($result, true);
    return $data['access_token'] ?? false;
}

// Retorna os dados de uma track dado o ID
function get_spotify_track($track_id, $token) {
    $ch = curl_init("https://api.spotify.com/v1/tracks/$track_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token"
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if (!$result) return false;
    return json_decode($result, true);
}

// Retorna os dados da playlist e as tracks
function get_spotify_playlist_tracks($playlist_id, $token) {
    $ch = curl_init("https://api.spotify.com/v1/playlists/$playlist_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token"
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if (!$result) return false;
    return json_decode($result, true);
}

// Extrair ID do link do spotify
function extract_spotify_id($link, $type = 'track') {
    if (preg_match("/spotify\.com\/$type\/([a-zA-Z0-9]+)/", $link, $matches)) {
        return $matches[1];
    }
    return false;
}
?>
