<?php
namespace SoundDetective\Update;

class Config{
    const db_host = "";
    const db_name = "sounddetective";
    const db_port = "3306";
    const db_user = "root";
    const db_password = "";

    const spotify_token = "";
    const last_fm_key = "";


    const table_update_albums = "albums";
    const table_update_artists = "artists";
    const table_update_related = "artists_related";
    const table_update_related_calculated = "artists_related_calculated";
    const table_update_songs = "songs";
    const table_update_tracks = "tracks";
    const table_update_charts = "charts";
}