<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="description" content="SoundDetective finds Music for you! You can find music you like and exclude songs and artists you don't like. Create playlists for spotify or listen to them on youtube. Love music and we suggest you music based on your taste! All free, fast and simple!">
    <meta name="keywords" content="music,search music,generate playlists,sound,sounddetective,sound detective,search music">
    <meta name="classification" content="Music">
    <meta name="copyright" content="Copyright 2015 Fabian Kramm">
    <meta name="application-name" content="SoundDetective" />

    <!-- VIEWPORT -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">

    <!-- <link rel="stylesheet" href="css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="css/jquery.auto-complete.css">
    <link rel="stylesheet" href="css/jquery.nouislider.min.css">
    <link rel="stylesheet" href="css/jquery.qtip.min.css">
    <link rel="stylesheet" href="css/chosen.min.css">
    <link rel="stylesheet" href="css/ionicons.min.css">
    <link rel="stylesheet" href="css/hover.min.css">
    <link rel="stylesheet" href="css/vex.css">
    <link rel="stylesheet" href="css/main.css">

    <!-- <link type="text/css" href="css/bootstrap-theme.min.css"> -->
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <title>
        Sound Detective
    </title>
    <script src="js/requirejs.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        require.config({
            baseUrl: 'js',
            paths: {
                "jquery": "jquery-2.1.4.min",
                "FB": "https://connect.facebook.net/en_US/sdk",
            },
            shim: {
                'FB': {
                    exports: 'FB'
                },
                'jquery.auto-complete.min': {
                    deps: ['jquery'],
                    exports: 'jQuery.fn.autoComplete'
                },
                'jquery.nouislider.all.min': {
                    deps: ['jquery'],
                    exports: 'jQuery.fn.noUiSlider'
                },
                'jquery.qtip.min': {
                    deps: ['jquery'],
                    exports: 'jQuery.fn.qtip'
                },
                'chosen.jquery.min': {
                    deps: ['jquery'],
                    exports: 'jQuery.fn.chosen'
                }
            }
        });

        <?php
            if( !isset($_SESSION['user_id']) ){
                echo "window.logged_in = false;";
                echo "window.login_mode = 0;";
            }
            else{
                echo "window.logged_in = true;";

                if( isset($_SESSION['access_token']) ){
                    echo "window.login_mode = 1;";
                }
                else if( isset($_SESSION['facebook_login']) ){
                    echo "window.login_mode = 2;";
                }
                else{
                    echo "window.login_mode = 0;";
                }
            }
        ?>

        // Let's go!
        require(["main"]);
    </script>
</head>
<body>
    <script id="exploreArtistTemplate" type="text/sd-template">
        <div class="exploreArtistTile" data-id="{{id}}" data-name="{{name}}" data-text="{{similar_text}}"><a href="javascript:void(0)" data-id="{{id}}"><img src="{{image_small}}" /></a></div>
    </script>
    <script id="modifyDetectivesExportPopupTemplate" type="text/sd-template">
        <div class="popup-prompt">
            Please copy the detective uuid:
            <div>
                <input class="input" value="{{uuid}}" readonly/>
            </div>
            <div class="popup-btn-wrapper">
                <span style="color: white" class="mdt-export-btn btn-primary popup-yes">OK</span>
            </div>
        </div>
    </script>
    <script id="modifyDetectivesImportTemplate" type="text/sd-template">
        <div class="popup-import">
            <div style="text-align: left; margin-bottom: 15px;">
                <select data-placeholder="Select A Detective" class="mdt-import-select">
                    {{#if public_detectives}}
                        <optgroup label="Your Detectives">
                            {{#each detectives}}
                            <option value="{{uuid}}">{{name}}</option>
                            {{/each}}
                        </optgroup>
                        <optgroup label="Public Detectives">
                            {{#each public_detectives}}
                            <option value="{{uuid}}">{{name}}</option>
                            {{/each}}
                        </optgroup>
                    {{else}}
                        {{#each detectives}}
                        <option value="{{uuid}}">{{name}}</option>
                        {{/each}}
                    {{/if}}
                </select>
            </div>

            <div class="small_line"></div>
            <div class="big_or">OR</div>

            <div style="margin-top: 15px;">
                <input placeholder="Enter UUID" class="mdt-import-input input" />
            </div>

            <div class="popup-btn-wrapper">
                <span class="mdt-popup-import btn-primary popup-yes">Import</span>
                <span class="mdt-popup-cancel btn-secondary popup-cancel">Cancel</span>
            </div>
        </div>
    </script>
    <script id="excludeHoverTemplate" type="text/sd-template">
        <div>
            <div class="excludeHoverBtn" onclick="EventPipe.trigger('{{event_name_exsong}}',{{db_id}});">Exclude Song</div>
            <div class="excludeHoverBtn" onclick="EventPipe.trigger('{{event_name_exartist}}',{{artist_id}});">Exclude Artist</div>
        </div>
    </script>
    <script id="passwordChangeTemplate" type="text/sd-template">
        <div class="form-view register-content">
            <div class="sd-headline">Change Password</div>

            <div class="mdt-wrapper">
                <form class="changePasswordForm" name="changePasswordForm" action="index.php" method="post">
                    <input type="hidden" name="a" value="changepassword" />
                    <input type="hidden" name="id" value="{{reset_id}}" />
                    <input type="hidden" name="key" value="{{reset_key}}" />
                    <div>
                        <span class="exNameHeadline">New Password: </span>
                        <div>
                            <input name="password" class="register-input input" type="password" placeholder="Password" />
                        </div>
                    </div>
                    <div>
                        <span class="exNameHeadline">Confirm Password: </span>
                        <div>
                            <input name="confirm_password" class="register-input input" type="password" placeholder="Password" />
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <input class="btn btn-primary registerBtn" type="submit" value="Change Password" />
                    </div>
                </form>
            </div>
        </div>
    </script>
    <script id="passwordResetTemplate" type="text/sd-template">
        <div class="form-view reset-password-content">
            <div class="sd-headline">Reset Password</div>

            <div class="mdt-wrapper">
                <form class="resetPasswordForm" name="resetPasswordForm" action="index.php" method="post">
                    <input type="hidden" name="a" value="forgot_password" />
                    <div>
                        <span class="exNameHeadline">Enter your E-Mail Address: </span>
                        <div>
                            <input name="email" class="register-input input" type="text" placeholder="E-Mail" />
                        </div>
                        <div class="register-recaptcha">
                            <div class="g-recaptcha"></div>
                        </div>
                    </div>
                    <div>
                        <input class="btn btn-primary registerBtn" type="submit" value="Reset Password" />
                    </div>
                </form>
            </div>
        </div>
    </script>
    <script id="registerTemplate" type="text/sd-template">
        <div class="form-view register-content">
            <div class="sd-headline">Create New Account</div>

            <div class="mdt-wrapper">
                <form id="registerForm" name="registerForm" action="index.php" method="post">
                    <input type="hidden" name="a" value="registerv2" />
                    <div>
                        <span class="exNameHeadline">Username: </span>
                        <div>
                            <input name="username" class="register-input input" type="text" placeholder="Username" />
                        </div>
                    </div>
                    <div>
                        <span class="exNameHeadline">Password: </span>
                        <div>
                            <input name="password" class="register-input input" type="password" placeholder="Password" />
                        </div>
                    </div>
                    <div>
                        <span class="exNameHeadline">Confirm Password: </span>
                        <div>
                            <input name="confirm_password" class="register-input input" type="password" placeholder="Password" />
                        </div>
                    </div>
                    <div>
                        <span class="exNameHeadline">E-Mail: </span>
                        <div>
                            <input name="email" class="register-input input" type="text" placeholder="E-Mail" />
                        </div>
                    </div>

                    <div class="register-recaptcha">
                        <div class="g-recaptcha"></div>
                    </div>

                    <div class="exNameHeadline register-agree">
                        I agree to the <a href="http://www.sounddetective.net/legal.html" target="_blank">Privacy Policy</a>: <input id="agreePolicy" type="checkbox">
                    </div>

                    <div>
                        <input class="btn btn-primary registerBtn" type="submit" value="Register" />
                    </div>
                </form>
            </div>
        </div>

    </script>
    <script id="promptTemplate" type="text/sd-template">
        <div class="popup-prompt">
            <div>{{text}}</div>
            <input placeholder="{{placeholder}}" class="promptInput input" value="{{inputText}}" />
            <div class="popup-btn-wrapper">
                <span class="btn-primary submitBtn popup-yes">Create</span>
                <span class="btn-secondary closeBtn popup-cancel">Cancel</span>
            </div>
        </div>
    </script>
    <script id="promptPlaylistTemplate" type="text/sd-template">
        <div class="popup-prompt popup-playlist-prompt">
            <div>{{text}}</div>
            <input placeholder="{{placeholder}}" class="promptInput input" value="{{inputText}}" />
            <div class="chkbox-wrapper">
                <input type="checkbox" class="exclude-songs-chkbox"><span>Exclude Songs From Detective</span>
            </div>
            <div class="popup-btn-wrapper">
                <span class="btn-primary submitBtn popup-yes">Create</span>
                <span class="btn-secondary closeBtn popup-cancel">Cancel</span>
            </div>
        </div>
    </script>
    <script id="alertTemplate" type="text/sd-template">
        <div class="alert-wrapper">
            <span>{{message}}</span>
            <div>
                <span class="closeBtn btn-primary">Ok</span>
            </div>
        </div>
    </script>
    <script id="loginFormTemplate" type="text/sd-template">
        <div class="login-form">
            <div class="spotify_login login-button" onclick="EventPipe.trigger('User-loginSpotify')"><span class="login-spotify spotifyLink"></span><span>Login with Spotify</span></div>
            <div class="facebook_login login-button" onclick="EventPipe.trigger('User-loginFacebook')"><span class="login-facebook ion-social-facebook"></span><span>Login with Facebook</span></div>
            <div class="small_line"></div>
            <div class="big_or">OR</div>
            <div>
                <div class="vex-dialog-form">
                    <form id="loginForm" name="loginForm" method="POST" action="index.php">
                        <div class="no-margin-bottom">
                            <input class="input login-username" type="text" placeholder="Username" name="username" />
                        </div>
                        <div class="">
                            <input class="input login-password" name="password" type="password" placeholder="Password" />
                        </div>
                    </form>
                </div>
                <div class="password-forgotten">
                    <a href="javascript:void(0)" class="reset-password-link">Reset Password?</a>
                </div>
                <div style="text-align: center">
                    <div class="normal_login btn-primary" onclick="EventPipe.trigger('User-loginNormal')"><span class="ion-ios-person"></span><span>Login</span></div>
                    <input id="loginSubmit" type="submit" style="display:none" />
                    <div class="register-form">
                        No account yet? <a class="register-link" href="javascript:void(0)" >Register</a>
                    </div>
                </div>
            </div>
        </div>
    </script>
    <script id="CurrentlyPlayingTemplate" type="text/x-handlebars-template">
        <div class="cpv-overlay">

        </div>
        <div class="cpv-content">
            <div class="mobile-back">
                <span class="artistLink">Back</span>
            </div>
            <div class="cpv-scroll">
                <div>
                    Playing Next:
                </div>
                <div class="cpv-songs">

                </div>
            </div>

        </div>
    </script>
    <script id="CurrentlyPlayingSongTemplate" type="text/x-handlebars-template">
        <div class="song" data-id="{{db_id}}">
            <span class="songExcludeBtn ion-close"></span><span class="songTextWrapper" title="{{#each artists}}{{artist_name}}{{#if @last}}{{else}},{{/if}}{{/each}} - {{title}}">
                {{#each artists}}
                    <a class="artistLink" data-artist="{{db_artist_id}}">{{artist_name}}</a>{{#if @last}}{{else}},{{/if}}
                {{/each}}
                - {{title}} </span><span class="songPlayBtnWrapper"><a class="ytLink ytLinkPlay" data-id="{{db_id}}"></a></span>
        </div>
    </script>
    <script id="SearchTemplate" type="text/x-handlebars-template">
        <div>
            <div class="sv-overlay">

            </div>
            <div class="sv-content">
                <div class="mobile-back">
                    <span class="artistLink">Back</span>
                </div>
                <div class="mobile-back" style="text-align: center;margin-top: 20px;">
                    <input class="input search-artists" type="input" placeholder="Search Artist" />
                </div>
                <div class="sv-search-artists-headline">Search Artists</div>
                <div class="sv-loader" style="display:none">
                    <div class="sk-circle">
                        <div class="sk-circle1 sk-child"></div>
                        <div class="sk-circle2 sk-child"></div>
                        <div class="sk-circle3 sk-child"></div>
                        <div class="sk-circle4 sk-child"></div>
                        <div class="sk-circle5 sk-child"></div>
                        <div class="sk-circle6 sk-child"></div>
                        <div class="sk-circle7 sk-child"></div>
                        <div class="sk-circle8 sk-child"></div>
                        <div class="sk-circle9 sk-child"></div>
                        <div class="sk-circle10 sk-child"></div>
                        <div class="sk-circle11 sk-child"></div>
                        <div class="sk-circle12 sk-child"></div>
                    </div>
                </div>
                <div class="sv-artist-message">
                    <span class="ion-ios-search-strong"></span>
                    <span>Search For <br>Your Favourite Artists</span>
                </div>
                <div class="sv-results">

                </div>
            </div>
        </div>
    </script>
    <script id="SearchArtistTemplate" type="text/x-handlebars-template">
        <div class="sv-artist" data-id="{{id}}">
            <object data="{{image_small}}" type="image/png">
                <img src="img/SD.png" />
            </object>
            <span>{{name}}</span>
        </div>
    </script>
    <script id="PlayerTemplate" type="text/x-handlebars-template">
        <div id="Player">
            <div id="ytWrapper">
                <div id="scPlayer-Wrapper">
                    <iframe id="scPlayer" src="about:blank" frameborder="0" width="250" height="200" />
                </div>
                <div id="ytPlayer-Wrapper">
                    <div id="ytPlayer">
                    </div>
                </div>
                <div class="playerWrongVideo">
                    <span>Wrong Video?</span>
                </div>
                <div class="ytNowPlaying">
                    <div class="ytTitle">

                    </div>
                    <div class="ytArtist">
                        <span class="ytArtist1">

                        </span>
                        <span class="ytArtist2">

                        </span>
                        <span class="ytArtist3">

                        </span>
                    </div>
                    <div style="display: none;" class="ytLoading sk-circle">
                        <div class="sk-circle1 sk-child"></div>
                        <div class="sk-circle2 sk-child"></div>
                        <div class="sk-circle3 sk-child"></div>
                        <div class="sk-circle4 sk-child"></div>
                        <div class="sk-circle5 sk-child"></div>
                        <div class="sk-circle6 sk-child"></div>
                        <div class="sk-circle7 sk-child"></div>
                        <div class="sk-circle8 sk-child"></div>
                        <div class="sk-circle9 sk-child"></div>
                        <div class="sk-circle10 sk-child"></div>
                        <div class="sk-circle11 sk-child"></div>
                        <div class="sk-circle12 sk-child"></div>
                    </div>
                </div>
                <div id="ytControls">
                    <div>
                        <span class="ytBackward ion-ios-skipbackward"></span>
                        <span class="ytPlay ion-ios-play"></span>
                        <span class="ytForward ion-ios-skipforward"></span>
                    </div>
                    <div class="ytControls-secondRow">
                        <span class="volumeControl ion-volume-medium">
                            <div class="volumeWrapper">
                                <div class="volumeSlider smallSpinner">

                                </div>
                            </div>
                        </span>
                        <span class="removeSong ion-close"></span>
                        <span class="loveSong ion-heart"></span>
                        <span><a class="spotifyLink"></a></span>
                        <span class="showSongQueue ion-android-list"></span>
                    </div>
                </div>
            </div>
            <div id="spotifyPlayer">
                <iframe id="spotifyIframe" style="width:250px;height:330px;margin-bottom:-4px;" src="about:blank" frameborder="0" allowtransparency="true"></iframe>
            </div>
        </div>
    </script>
    <script id="ChartsTemplate" type="text/x-handlebars-template">
        <div>
            <div class="sd-headline">Charts</div>
            <div class="sd-sub-headline">Check out whats currently popular</div>

            <div class="mdt-wrapper charts-wrapper">
                <div class="mdt-bar charts-bar">
                    <span class="hvr-underline-from-center charts-0 mdt-selected">All</span>
                    <span class="hvr-underline-from-center charts-1">Electronic</span>
                    <span class="hvr-underline-from-center charts-2">Rock</span>
                    <span class="hvr-underline-from-center charts-3">Pop</span>
                    <span class="hvr-underline-from-center charts-4">Hip Hop</span>
                </div>
                <div class="sk-circle">
                    <div class="sk-circle1 sk-child"></div>
                    <div class="sk-circle2 sk-child"></div>
                    <div class="sk-circle3 sk-child"></div>
                    <div class="sk-circle4 sk-child"></div>
                    <div class="sk-circle5 sk-child"></div>
                    <div class="sk-circle6 sk-child"></div>
                    <div class="sk-circle7 sk-child"></div>
                    <div class="sk-circle8 sk-child"></div>
                    <div class="sk-circle9 sk-child"></div>
                    <div class="sk-circle10 sk-child"></div>
                    <div class="sk-circle11 sk-child"></div>
                    <div class="sk-circle12 sk-child"></div>
                </div>
                <div class="songDisplay">

                </div>
            </div>
        </div>
    </script>
    <script id="ChartsViewSongTemplate" type="text/x-handlebars-template">
        <div class="song" data-id="{{db_id}}">
            <span class="songAddColl ion-heart {{in_collection}}" data-id="{{db_id}}"></span> <span class="songTextWrapper">
                {{#each artists}}
                    <a class="artistLink" data-artist="{{db_artist_id}}">{{artist_name}}</a>{{#if @last}}{{else}},{{/if}}
                {{/each}}
                - {{title}}</span> <span class="songPlayBtnWrapper"><a class="ytLink ytLinkPlay {{isNA}}" data-id="{{db_id}}"></a><a href="spotify:track:{{available_spotify_id}}" class="spotifyLinkBlack"></a></span>
        </div>
    </script>
    <script id="ArtistViewSongTemplate" type="text/x-handlebars-template">
        <div class="song" data-id="{{db_id}}">
            <span data-text="{{popularity_text}}" style="color: rgb({{popularity_color}},0,0);" class="songHotness ion-ios-flame"></span><span class="songAddColl ion-heart {{in_collection}}" data-id="{{db_id}}"></span><span data-release-date="{{release_date}}" class="songReleaseDate"></span> <span class="songTextWrapper">{{title}}</span> <span class="songPlayBtnWrapper"><a class="ytLink ytLinkPlay {{isNA}}" data-id="{{db_id}}"></a><a href="spotify:track:{{available_spotify_id}}" class="spotifyLinkBlack"></a></span>
        </div>
    </script>
    <script id="SongTemplate" type="text/x-handlebars-template">
        <div class="song" data-id="{{db_id}}">
           <span class="songExcludeBtn ion-close" data-id="{{db_id}}"></span>
            <span class="songAddColl ion-heart {{in_collection}}" data-id="{{db_id}}"></span>
            <span class="songReleaseDate">{{releaseDateString}}</span>
            <span class="songTextWrapper">
            {{#each artists}}
                <a class="artistLink" data-artist="{{db_artist_id}}">{{artist_name}}</a>{{#if @last}}{{else}},{{/if}}
            {{/each}}
            - {{title}}</span><span class="songPlayBtnWrapper"><a class="ytLink ytLinkPlay {{isNA}}" data-id="{{db_id}}"></a><a href="spotify:track:{{available_spotify_id}}" class="spotifyLinkBlack"></a></span>
        </div>
    </script>
    <script id="SongTemplateCollection" type="text/x-handlebars-template">
        <tr data-id="{{db_id}}">
            <td><span class="songExcludeBtn ion-heart" data-id="{{db_id}}"></span></td>
            <td>
                {{#each artists}}
                    <a class="artistLink" data-artist="{{db_artist_id}}">{{artist_name}}</a>{{#if @last}}{{else}},{{/if}}
                {{/each}}
            </td>
            <td>{{title}}</td>
            <td>{{is_in_collection}}</td>
            <td><a class="ytLink ytLinkPlay {{isNA}}" data-id="{{db_id}}"></a><a href="spotify:track:{{available_spotify_id}}" class="spotifyLinkBlack"></a></td>
        </tr>
    </script>
    <script id="ExploreTemplate" type="text/x-handlebars-template">
        <div>
            <div class="exDetectiveWrapper">
                <div class="sd-headline">
                    Play Detective
                </div>
                <div class="exDetective">
                    <div style="width:100%; position:relative;">
                        <div class="exImageWrapper">
                            <div>
                                <span class="exDetectiveName"></span>
                            </div>
                            <img class="exDetectiveImg" />
                        </div>
                        <div class="exploreHeader">
                            <div>
                                <div class="exNameHeadline" style="padding-top: 0px;">Artists</div>
                                <div class="exArtistsContainer"></div>
                                <div class="exNameHeadline">Options</div>
                                <div class="exOptionsContainer">
                                    <div>
                                        <span class="exOptionsLabel">Songs:</span> <span class="songPop"></span>
                                    </div>
                                    <div>
                                        <span class="exOptionsLabel">Artists:</span> <span class="artistPop"></span>
                                    </div>
                                    <div>
                                        <span class="exOptionsLabel">Release:</span><span class="releaseDate"></span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="btn-secondary ion-edit exEdit"></div>
                    <div>
                        <div class="exBtnWrapper">
                            <span class="btn-secondary ion-android-sync right ev-refresh"></span>
                            <span class="btn-primary createTrackset" id="createTrackset"><span class="spotifyLink exSpBtn"></span>Trackset</span>
                            <span class="btn-primary createPlaylist"><span class="spotifyLink exSpBtn"></span>Playlist</span>
                        </div>
                        <div class="songDisplay">

                        </div>
                    </div>
                    <div class="sk-circle">
                        <div class="sk-circle1 sk-child"></div>
                        <div class="sk-circle2 sk-child"></div>
                        <div class="sk-circle3 sk-child"></div>
                        <div class="sk-circle4 sk-child"></div>
                        <div class="sk-circle5 sk-child"></div>
                        <div class="sk-circle6 sk-child"></div>
                        <div class="sk-circle7 sk-child"></div>
                        <div class="sk-circle8 sk-child"></div>
                        <div class="sk-circle9 sk-child"></div>
                        <div class="sk-circle10 sk-child"></div>
                        <div class="sk-circle11 sk-child"></div>
                        <div class="sk-circle12 sk-child"></div>
                    </div>

                </div>
            </div>
        </div>
    </script>
    <script id="ArtistTemplate" type="text/x-handlebars-template">
        <div class="av-overlay">
        </div>
        <div class="av-content">
            <div class="av-loader">
                <div class="sk-circle">
                    <div class="sk-circle1 sk-child"></div>
                    <div class="sk-circle2 sk-child"></div>
                    <div class="sk-circle3 sk-child"></div>
                    <div class="sk-circle4 sk-child"></div>
                    <div class="sk-circle5 sk-child"></div>
                    <div class="sk-circle6 sk-child"></div>
                    <div class="sk-circle7 sk-child"></div>
                    <div class="sk-circle8 sk-child"></div>
                    <div class="sk-circle9 sk-child"></div>
                    <div class="sk-circle10 sk-child"></div>
                    <div class="sk-circle11 sk-child"></div>
                    <div class="sk-circle12 sk-child"></div>
                </div>
            </div>
            <div class="mobile-back">
                <span class="artistLink">Back</span>
            </div>
            <div class="av-artist-header">
                <div class="av-artist-header-background">

                </div>
                <div class="av-artist-header-foreground">
                    <div class="av-artist-image-wrapper">
                        <img class="av-artist-image"  src="img/SD.png" />
                    </div>
                    <div class="av-info-wrapper">
                        <div class="av-heading">Artist</div>
                        <div class="av-artist-name"></div>

                        <div class="av-heading">Genres</div>
                        <div class="av-genre-wrapper">

                        </div>
                    </div>
                </div>

            </div>
            <div class="mdt-wrapper artist-wrapper">
                <div class="mdt-bar">
                    <span class="av-sort-by">
                        <select class="selectField" style="width:350px;">
                            <option value="1">Popularity</option>
                            <option value="2">Song Name</option>
                            <option value="3">Release Date</option>
                        </select>
                    </span>
                    <span class="av-top-songs-bar hvr-underline-from-center mdt-selected">Top Songs</span>
                    <span class="av-related-bar hvr-underline-from-center">Related Artists</span>
                    <span class="av-filter">
                        <input class="searchField" placeholder="Filter" />
                    </span>
                </div>
                <div class="av-songDisplay">

                </div>
                <div class="av-related-artists">

                </div>
            </div>
        </div>
    </script>
    <script id="ArtistGenreTemplate" type="text/x-handlebars-template">
        <span class="av-genre">{{name}}</span>
    </script>
    <script id="ArtistRelatedTemplate" type="text/x-handlebars-template">
        <div data-artist="{{id}}">
            <object data="{{image}}" type="image/png">
                <img src="img/SD.png" />
            </object>
            <span data-artist="{{id}}" class="av-related-artist"><span>{{name}}</span></span>
        </div>
    </script>
    <script id="CollectionTemplate" type="text/x-handlebars-template">
        <div>
            <div class="sd-headline">Collection</div>

            <div class="mdt-wrapper">
                <div>
                    <div>
                        <span class="btn-primary createTrackset"><span class="spotifyLink exSpBtn"></span>Trackset</span>
                    </div>
                    <div>
                        <input type="text" placeholder="Search In Collection" class="cv-search searchField" />
                    </div>
                </div>
                <div class="collectionTable">
                    <table>
                        <thead><th>&nbsp;</th><th class="artistTablHdr" width="30%">Artist<span></span></th><th class="titleTablHdr" width="30%">Title<span></span></th><th class="addedTablHdr" width="20%">Added<span></span></th><th>&nbsp;</th></thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </script>
    <script id="ShowDetectivesTemplate" type="text/x-handlebars-template">
        <div>
            <div class="sd-headline">Welcome to Sound Detective!</div>
            <div class="sd-sub-headline">Click on a detective to start</div>

            <div class="sd-underlined-sub-headline">Your Detectives</div>
            <div class="myDetectivesWrapper">
                <div class="myDetectives">
                    <div class="newDetective">
                        <div class="newDetective-border">
                        </div>
                        <span><span class="sd-create-plus ion-plus-round"></span>Detective</span>
                    </div>

                </div>
            </div>

            <div class="sd-underlined-sub-headline">Public Detectives
                <span class="sd-select-genres">
                    <select class="selectField" style="width:350px;">
                        <option value="1">Popular</option>
                        <option value="2">Pop</option>
                        <option value="3">Electronic</option>
                        <option value="4">Party</option>
                        <option value="5">Rock & Metal</option>
                        <option value="6">Hip Hop</option>
                        <option value="7">Other</option>
                    </select>
                </span>
            </div>
            <div class="publicDetectives">

            </div>
        </div>
    </script>
    <script id="DetectiveTemplate" type="text/x-handlebars-template">
        <div data-uuid="{{uuid}}" class="detective-wrapper" style="animation-delay: {{delay}};">
            <div><img src="{{image}}" /></div>
            <a class="detective" data-id="{{idx}}"><span>{{name}}</span></a>

            <a class="detective-delete ion-trash-b {{show}}" data-id="{{idx}}"></a>
            <a class="detective-edit ion-edit" data-id="{{idx}}"></a>
        </div>
    </script>
    <script id="ModifyDetectivesTemplate" type="text/x-handlebars-template">
        <div>
            <div class="mdt-headline">Create New Detective</div>

            <div class="mdt-wrapper">
                <div class="mdt-bar mobile-hide">
                    <span class="hvr-underline-from-center mdt-bar-step-1">1. Artists</span>
                    <span class="hvr-underline-from-center mdt-bar-step-2">2. Advanced</span>
                    <span class="hvr-underline-from-center mdt-bar-step-3">3. Exclude Artist</span>
                    <span class="hvr-underline-from-center mdt-bar-step-4">4. Exclude Song</span>
                </div>
                <div class="mdt-bar mobile">
                    <span class="hvr-underline-from-center mdt-bar-step-1">Artists</span>
                    <span class="hvr-underline-from-center mdt-bar-step-2">Options</span>
                    <span class="hvr-underline-from-center mdt-bar-step-3">Ex. Artists</span>
                    <span class="hvr-underline-from-center mdt-bar-step-4">Ex. Songs</span>
                </div>
                <div class="mdt-step-wrapper">
                    <div class="mdt-step-1 mdt-step-view">
                        <span class="mdt-btn-wrapper-step-1">
                            <span class="btn-secondary right mdt-import-settings ion-android-download"></span>
                            <span class="btn-secondary right mdt-export-btn ion-android-upload"></span>
                            <span class="btn-secondary right mdt-delete-detective ion-trash-b"></span>
                        </span>


                        <!-- <img class="detectiveImage" /> -->
                        <div class="exNameHeadline">
                            <div>Name:</div>
                            <input placeholder="Detective Name" class="input detectiveName" />
                        </div>


                        <div class="exNameHeadline">
                            <div>Include artists you like:</div>
                            <input id="mdt-search-artist" class="input" placeholder="Add Artist" />
                            <div class="mdt-popular-artists-wrapper">
                                Popular Artists: <span class="mdt-popular-artists"></span>
                            </div>
                        </div>
                        <div class="exNameHeadline">
                            <div class="mdt-table-scroller">
                                <table class="mdt-artist-table">
                                    <thead>
                                    <tr>
                                        <th width="100px">&nbsp;</th>
                                        <th width="50%">Artist Name</th>
                                        <th width="40%">Include Similar Artists</th>
                                    </tr>
                                    </thead>
                                    <tbody class="mdt-search-artists">

                                    </tbody>
                                </table>
                            </div>
                            <div class="mdt-artists-total-wrapper">Total Artists: <span class="mdt-artist-total">?</span></div>
                        </div>
                        </div>
                    <div class="mdt-step-2 mdt-step-view">
                        <div>
                            Song Popularity:<div id="mdt-song-popularity" class="slider optionSlider"></div>
                        </div>
                        <div>
                            Artist Popularity:<div id="mdt-artist-popularity" class="slider optionSlider"></div>
                        </div>
                        <div>
                            Release Date:<div id="mdt-release-date" class="slider optionSlider"></div>
                        </div>
                        <div class="mdt-chkbx-wrapper">
                            <div>
                                <span>Exclude Remix: </span><input id="mdt-exclude-remix" type="checkbox" />
                            </div>
                            <div>
                                <span>Exclude Acoustic: </span><input id="mdt-exclude-acoustic" type="checkbox" />
                            </div>
                            <div>
                                <span>Exclude Collection: </span><input id="mdt-exclude-collection" type="checkbox" />
                            </div>
                        </div>
                    </div>
                    <div class="mdt-step-3 mdt-step-view">
                        <span class="btn-secondary right mdt-import-excluded-artists ion-android-download"></span>
                        <div class="exNameHeadline">
                            <div>
                                Exclude Artist:
                            </div>
                            <input placeholder="Add Artist" class="input mdt-exclude-artist-input" />
                        </div>
                        <div class="mdt-exclude-artist-wrapper">
                            <div>
                                <span class="btn-secondary mdt-clear-excluded-artists">Clear</span>
                                <span><input class="mdt-exclude-artists-filter searchField" placeholder="Filter" /></span>
                            </div>
                            <div class="mdt-table-scroller">
                                <table class="mdt-artist-table mdt-exclude-artist-table">
                                    <thead>
                                    <tr>
                                        <th width="30px">&nbsp;</th>
                                        <th>Name</th>
                                    </tr>
                                    </thead>
                                    <tbody class="mdt-exclude-artists">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="mdt-step-4 mdt-step-view">
                        <span class="btn-secondary right mdt-import-excluded-songs ion-android-download"></span>
                        <div class="mdt-exclude-artist-wrapper">
                            <div>
                                <span class="btn-secondary mdt-clear-excluded-songs">Clear</span>
                                <span><input class="mdt-exclude-songs-filter searchField" placeholder="Filter" /></span>
                            </div>
                            <div class="mdt-table-scroller">
                                <table class="mdt-artist-table mdt-exclude-song-table">
                                    <thead>
                                    <tr>
                                        <th width="30px">&nbsp;</th>
                                        <th>Artist</th>
                                        <th>Title</th>
                                    </tr>
                                    </thead>
                                    <tbody class="mdt-exclude-songs">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mdt-btn-wrapper">
                <span class="mdt-abort btn-secondary">Cancel</span>
                <span class="mdt-save btn-primary">Save</span>
            </div>

        </div>
    </script>
    <script id="mdtArtistTemplate" type="text/x-handlebars-template">
        <tr>
            <td><span class="songExcludeBtn mdt-artist-remove-btn ion-close" data-idx="{{idx}}"></span></td>
            <td><img src="{{image_small}}" /><a href="javascript:void(0)" class="mdt-artist-name" data-id="{{id}}" data-distance="{{distance}}">{{name}}</a></td>
            <td><div data-id="{{id}}" data-idx="{{idx}}" class="edgeSpinner slider-{{idx}}"></div><span style="font-size: 14px;">None</span></td>
        </tr>
    </script>
    <script id="mdtArtistTemplateEmpty" type="text/x-handlebars-template">
        <tr>
            <td class="mdt-no-artists" style="text-align:center" colspan="{{col_span}}">{{message}}</td>
        </tr>
    </script>
    <script id="mdtExcludeSongTemplate" type="text/x-handlebars-template">
        <tr>
            <td><span class="songExcludeBtn ion-close mdt-song-exclude-delete" data-idx="{{idx}}"></span></td>
            <td>
                <div data-id="{{id}}">{{artist_name}}</div>
            </td>
            <td><div data-id="{{id}}">{{title}}</div></td>
        </tr>
    </script>
    <script id="mdtExcludeArtistTemplate" type="text/x-handlebars-template">
        <tr>
            <td><span class="songExcludeBtn ion-close mdt-artist-exclude-delete" data-idx="{{idx}}"></span></td>
            <td><img src="{{image_small}}" /><a href="javascript:void(0)" class="mdt-artist-name" data-id="{{id}}">{{name}}</a></td>
        </tr>
    </script>
    <script id="loaderTemplate" type="text/sd-template">
        <div class="sk-circle">
            <div class="sk-circle1 sk-child"></div>
            <div class="sk-circle2 sk-child"></div>
            <div class="sk-circle3 sk-child"></div>
            <div class="sk-circle4 sk-child"></div>
            <div class="sk-circle5 sk-child"></div>
            <div class="sk-circle6 sk-child"></div>
            <div class="sk-circle7 sk-child"></div>
            <div class="sk-circle8 sk-child"></div>
            <div class="sk-circle9 sk-child"></div>
            <div class="sk-circle10 sk-child"></div>
            <div class="sk-circle11 sk-child"></div>
            <div class="sk-circle12 sk-child"></div>
        </div>
    </script>
    <script id="mobileSidebarTemplate" type="text/sd-template">
        <div class="msb-overlay">
        </div>
        <div class="msb-content">
            <ul class="nav">
                <li class="search"><span class="ion-search"></span><a>Search Artist</a></li>
                <li class="explore selected"><span class="ion-android-home"></span><a>Detectives</a></li>
                <li class="charts"><span class="ion-podium"></span><a>Charts</a></li>
                <li class="collection"><span class="ion-filing"></span><a>Collection</a></li>
            </ul>

            <div class="user-btn-wrapper">
                <span style="display:none" class="login btn-primary"><span class="ion-person"></span><a>Login</a></span>
                <span style="display:none" class="logout btn-primary"><span class="ion-log-out"></span><a>Logout</a></span>
            </div>
        </div>
    </script>

    <div class="container-fluid" style="display: none">
        <div id="mobileHeader">
            <div class="burger-menu-wrapper">
                <div class="burger-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            <div class="logo-mobile">
                <img src="img/logo.png" />
            </div>
            <div class="player-btn ion-chevron-up">

            </div>
        </div>

        <div id="mobileSidebar">

        </div>

        <div id="sidebar" class="sidebar">
            <div class="sdb-overlay"></div>

            <img class="logo" src="img/logo.png" />
            <div class="searchWrapper">
                <input id="search" class="input" type="input" placeholder="Search Artist" />
                <span class="search-icon ion-ios-search-strong"></span>
            </div>

            <ul class="nav">
                <li class="explore"><span class="ion-android-home"></span><a>Detectives</a></li>
                <li class="charts"><span class="ion-podium"></span><a>Charts</a></li>
                <li class="collection"><span class="ion-filing"></span><a>Collection</a></li>

            </ul>

            <div class="user-btn-wrapper">
                <span style="display:none" class="login btn-primary"><span class="ion-person"></span><a>Login</a></span>
                <span style="display:none" class="logout btn-primary"><span class="ion-log-out"></span><a>Logout</a></span>
            </div>
        </div>

        <div id="content" class="content">
            <a href="#0" class="cd-top">Top</a>
        </div>
    </div>

    <div id="LoadingScreen" class="vertical-centered-box">
        <div class="loading-content">
            <div class="loader-circle"></div>
            <div class="loader-line-mask">
                <div class="loader-line"></div>
            </div>
            <img src="img/SDForeground.png" />
        </div>
    </div>

    <div id="LoadingIndicator" style="display: none;">
        <div class="loading-indicator-box">
            <div class="sk-circle">
                <div class="sk-circle1 sk-child"></div>
                <div class="sk-circle2 sk-child"></div>
                <div class="sk-circle3 sk-child"></div>
                <div class="sk-circle4 sk-child"></div>
                <div class="sk-circle5 sk-child"></div>
                <div class="sk-circle6 sk-child"></div>
                <div class="sk-circle7 sk-child"></div>
                <div class="sk-circle8 sk-child"></div>
                <div class="sk-circle9 sk-child"></div>
                <div class="sk-circle10 sk-child"></div>
                <div class="sk-circle11 sk-child"></div>
                <div class="sk-circle12 sk-child"></div>
            </div>
        </div>
    </div>

    <div id="LoadingSlider">

    </div>

    <!-- Google -->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-67050551-1', 'auto');
        ga('send', 'pageview');
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer>
    </script>
</body>
</html>