<?php
namespace SoundDetective\Util;

class Config{
    const playUrl = "http://play.sounddetective.net";
    const websiteUrl = "http://sounddetective.net";

    const recaptcha_private_key = "";

    const spotify_refresh_token = "";
    const spotify_redirect_token = "";

    const facebook_access = "";

    const youtube_api_key = "";

    const db_host = "127.0.0.1";
    const db_name = "sounddetective";
    const db_port = "3306";
    const db_user = "root";
    const db_password = "";

    const email_host = "";
    const email_username = "";
    const email_password = "";
    const email_from = "noreply@sounddetective.net";

    /**
     * Table Constants
     */
    const table_albums = "albums";
    const table_genres = "genres";
    const table_ip2location = "ip2location";

    const table_artists = "artists";
    const table_artists_genre = "artists_genre";
    const table_artists_related = "artists_related";
    const table_artists_related_calculated = "artists_related_calculated";

    const table_songs = "songs";
    const table_songs_youtube = "songs_youtube";

    const table_tracks = "tracks";
    const table_charts = "charts";

    const table_user = "user";
    const table_user_collection = "user_collection";
    const table_user_track = "user_track";

    const table_wrong_video = "wrong_video";

    const table_detective = "detective";
    const table_detective_artists = "detective_artists";
    const table_detective_exclude_artists = "detective_exclude_artists";
    const table_detective_exclude_songs = "detective_exclude_songs";
}