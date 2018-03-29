/**
 * Copyright SoundDetective 2016
 * @author Fabian Kramm (support@sounddetective.net)
 */
require(["jquery", "underscore", "handlebars", "backbone", "vex", "jquery.qtip.min"], function($, _, Handlebars, Backbone, vex){
    vex.defaultOptions.className = 'vex-theme-plain';

    /**
     * Global Constants
     */
    var Constants = {
        baseUrl: "http://play.sounddetective.net/index.php",
        playerView: "playerView",
        artistView: "artistView",
        exploreView: "exploreView",
        contentView: "contentView",
        searchView: "searchView",
        collectionView: "collectionView",
        chartsView: "chartsView",
        modifyDetectivesView: "modifyDetectivesView",
        showDetectivesView: "showDetectivesView",
        currentlyPlayingView: "currentlyPlayingView",
        sidebarView: "sidebar",
        mobileSidebarView: "mobileSidebar",
        registerView: "registerView",
        passwordResetView: "passwordResetView",
        passwordChangeView: "passwordChangeView",
        imageUrl: "img/SD.png",
        smallImageNotFoundUrl: "img/SD.png",
        bigImageNotFoundUrl: "img/SD.png",
        backgroundImageNotFoundUrl: "img/SD.png",
        recaptchaKey: "6LdJT04UAAAAAEqkE6y36nC3UrwWjnPq7LNZhZdA",
        currentTime: new Date().getTime(),
        loginMode: {
            normalLogin: 0,
            spotifyLogin: 1,
            facebookLogin: 2
        },
        routes: {
            index: "index",
            showDetectives: "showDetectives",
            charts: "charts",
            collection: "collection",
            showArtist: "showArtist/",
            applyDetective: "applyDetective/",
            modifyDetective: "modifyDetective/"
        }
    };

    /**
     * Router
     */
    var SoundDetectiveRouter = Backbone.Router.extend({
        initialize: function(){
            EventPipe.on("router.navigate", function(route, options){
                this.navigate(route, options || {});
            }.bind(this));
        },

        routes: {
            "" : "index",
            "showDetectives" : "showDetectives",
            "charts" : "charts",
            "collection" : "collection",
            "showArtist/:id" : "showArtist",
            "resetPassword/:id/:key" : "resetPassword",
            "applyDetective/:id" : "applyDetective",
            "modifyDetective/:uuid" : "modifyDetective"
        },

        resetPassword: function(id, key){
            EventPipe.trigger(Constants.contentView + "-showPasswordChangeView", {
                    id:id,
                    key:key
                });
        },

        index: function(){
            EventPipe.trigger(Constants.contentView + "-showDetectivesView");
        },

        showDetectives: function(){
            this.index();
        },

        charts: function(){
            EventPipe.trigger(Constants.contentView + "-showChartsView");
        },

        collection: function(){
            EventPipe.trigger(Constants.contentView + "-showCollectionView");
        },

        showArtist: function(id){
            EventPipe.trigger(Constants.contentView + "-openArtistView", id);
        },

        applyDetective: function(uuid){
            if( !uuid ){
                return;
            }

            var id = GlobalCollections.detectives.toJSON().map(function(el,idx){return el.uuid;}).indexOf(uuid);

            if( id == -1 ){
                return;
            }

            if( parseInt(GlobalCollections.detectives.toJSON()[id].owner_id) == -1337 ){
                var tempDetectives = (localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];
                var idx = tempDetectives.map(function(el){
                    return el.uuid;
                }).indexOf(GlobalCollections.detectives.toJSON()[id].uuid);

                EventPipe.trigger(Constants.contentView + "-showExploreView");
                EventPipe.trigger(Constants.exploreView + "-exploreDetective", {uuid: tempDetectives[idx].uuid, temp: tempDetectives[idx], name: tempDetectives[idx].name, image: (this.editMode) ? tempDetectives[idx].image : Constants.imageUrl}, true, true);
            }
            else{
                EventPipe.trigger(Constants.contentView + "-showExploreView");
                EventPipe.trigger(Constants.exploreView + "-exploreDetective", GlobalCollections.detectives.toJSON()[id], true, true);
            }
        },

        modifyDetective: function(uuid){
            if( !uuid || uuid == "0" ){
                EventPipe.trigger(Constants.contentView + "-createDetective");
            }
            else{
                EventPipe.trigger(Constants.contentView + "-editDetective", uuid);
            }
        }
    });

    /**
     * Util Object
     */
    var Util = {
        alertTemplate: null,
        promptTemplate: null,
        playlistPromptTemplate: null,
        //songPopularity: [[0, "Unpopular"],[40, "Slightly Popular"],[50, "Average Popular"],[60, "Popular"],[70, "Very Popular"]],
        //artistPopularity: [[0, "Unpopular"],[40, "Slightly Popular"],[55, "Popular"],[75, "Very Popular"]],
        songPopularity: [[10, "1 (Unpopular)"],[20,"2"],[30,"3"],[40,"4"],[45,"5"],[50,"6"],[55,"7"],[65,"8"],[75, "9 (Very Popular)"]],
        songPopularityLong: [[10, "Unpopular"],[20,"Unpopular"],[30,"Slightly Popular"],[40,"Slightly Popular"],[45,"Average Popular"],[50,"Popular"],[55,"Popular"],[65,"Very Popular"],[75, "Very Popular"]],
        artistPopularity: [[0, "1 (Unpopular)"],[20,"2"],[30,"3"],[40,"4"],[45,"5"],[50,"6"],[60,"7"],[70,"8"],[85, "9 (Very Popular)"]],
        artistPopularityLong: [[0, "Unpopular"],[20,"Unpopular"],[30,"Unpopular"],[40,"Slightly Popular"],[45,"Average Popular"],[50,"Average Popular"],[60,"Popular"],[70,"Very Popular"],[85, "Very Popular"]],


        linear_interpolation: function($X, $X1, $Y1, $X2, $Y2){
            return ( ( $X - $X1 ) * ( $Y2 - $Y1) / ( $X2 - $X1) ) + $Y1;
        },

        formatSong: function(str){
            return str;
        },

        getRandomInt: function(min, max) {
            return Math.floor(Math.random() * (max - min)) + min;
        },

        shuffle: function(a) {
            var j, x, i;
            for (i = a.length; i; i -= 1) {
                j = Math.floor(Math.random() * i);
                x = a[i - 1];
                a[i - 1] = a[j];
                a[j] = x;
            }
        },

        guid: function(){
            function s4() {
                return Math.floor((1 + Math.random()) * 0x10000)
                    .toString(16)
                    .substring(1);
            }
            return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
                s4() + '-' + s4() + s4() + s4();
        },

        alert: function(message){
            if( !this.alertTemplate ){
                this.alertTemplate = Handlebars.compile($("#alertTemplate").html());
            }

            vex.open({
                content: this.alertTemplate({message: message}),
                afterOpen: function($vexContent) {
                    $vexContent.find(".closeBtn").click(function(){
                        vex.close($vexContent.data().vex.id);
                    });

                    return;
                },
                afterClose: function() {
                    return;
                },
                showCloseButton: false
            });
        },

        prompt: function(placeholder, callback, text, inputText){
            if( !this.promptTemplate ){
                this.promptTemplate = Handlebars.compile($("#promptTemplate").html());
            }

            if( !text ){
                text = "";
            }

            vex.open({
                content: this.promptTemplate({placeholder: placeholder, text: text, inputText: inputText}),
                afterOpen: function($vexContent) {
                    $vexContent.find(".closeBtn").click(function(){
                        vex.close($vexContent.data().vex.id);
                    });

                    $vexContent.find(".submitBtn").click(function(){
                        vex.close($vexContent.data().vex.id);

                        if( callback ){
                            callback($vexContent.find(".promptInput").val());
                        }
                    });

                    return;
                },
                afterClose: function() {
                    return;
                },
                showCloseButton: false
            });
        },

        playlistPrompt: function(placeholder, callback, text, inputText){
            if( !this.playlistPromptTemplate ){
                this.playlistPromptTemplate = Handlebars.compile($("#promptPlaylistTemplate").html());
            }

            if( !text ){
                text = "";
            }

            vex.open({
                content: this.playlistPromptTemplate({placeholder: placeholder, text: text, inputText: inputText}),
                afterOpen: function($vexContent) {
                    $vexContent.find(".closeBtn").click(function(){
                        vex.close($vexContent.data().vex.id);
                    });

                    $vexContent.find(".submitBtn").click(function(){
                        vex.close($vexContent.data().vex.id);

                        if( callback ){
                            callback($vexContent.find(".promptInput").val(), $vexContent.find(".exclude-songs-chkbox").prop("checked"));
                        }
                    });

                    return;
                },
                afterClose: function() {
                    return;
                },
                showCloseButton: false
            });
        },

        validateEmail: function(email) {
            var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
            return re.test(email);
        },

        validateUsername: function(name) {
            var re = /^[\w]+$/i;
            return re.test(name);
        },

        validateDetectiveName: function(name) {
            var re = /^[\w ]+$/i;

            if( !re.test(name) ){
                return false;
            }

            if( !name || name.length == 0 || name.length > 127 ){
                return false;
            }

            return true;
        },

        urlParam: function(name){
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
            if (results==null){
                return null;
            }
            else{
                return decodeURIComponent(results[1]) || 0;
            }
        },

        showLoadingIndicator: function(){
            $("#LoadingIndicator").show();
        },

        hideLoadingIndicator: function(){
            $("#LoadingIndicator").hide();
        },

        loadingSlider: {
            interval: null,
            finish: false,
            progress: 0
        },

        showLoadingSlider: function(){
            var $LoadingSlider = $("#LoadingSlider");

            if( !this.loadingSlider.interval ){
                this.loadingSlider.finish = false;
                this.loadingSlider.progress = 0;

                $LoadingSlider.css("right", "100%")
                              .show();

                this.loadingSlider.interval = window.setInterval(function(){
                    this.loadingSlider.progress += 5;
                    $LoadingSlider.css("right", (100 - (Math.min(60, 25 * Math.log10(this.loadingSlider.progress)))) + "%");

                    if( this.loadingSlider.finish ){
                        $LoadingSlider.css("right", "30%");
                        window.clearInterval(this.loadingSlider.interval);

                        window.setTimeout(function(){
                            $LoadingSlider.css("right", "0%");

                            window.setTimeout(function(){
                                $LoadingSlider.hide();
                                this.loadingSlider.interval = null;
                            }.bind(this), 200);
                        }.bind(this), 200);
                    }
                }.bind(this), 100);
            }
        },

        finishLoadingSlider: function(){
            this.loadingSlider.finish = true;
        },

        getIndexFromNumber: function(n, arr){
            n = parseInt(n);

            for(var i=arr.length-1;i>=0;i--){
                if( n >= arr[i][0] ){
                    return i;
                }
            }

            return 0;
        },

        getStringFromNumber: function(n, arr){
            return arr[this.getIndexFromNumber(n, arr)][1];
        },

        getStringFromIndex: function(i, arr){
            return arr[parseInt(i)][1];
        },

        getNumberFromIndex: function(i, arr){
            return arr[parseInt(i)][0];
        },

        getUpperNumberFromIndex: function(i, arr){
            i = parseInt(i);

            if( i+1 >= arr.length ){
                return 100;
            }
            else{
                return arr[i+1][0] - 1;
            }
        },

        getSongPopularityString: function(song_pop){
            return this.getStringFromNumber(song_pop, this.songPopularity);
        },

        getSongPopularityLongString: function(song_pop){
            return this.getStringFromNumber(song_pop, this.songPopularityLong);
        },

        getArtistPopularityString: function(artist_pop){
            return this.getStringFromNumber(artist_pop, this.artistPopularity);
        },

        getArtistPopularityLongString: function(artist_pop){
            return this.getStringFromNumber(artist_pop, this.artistPopularityLong);
        }
    };

    /**
     * Global Models
     * @type {void|*}
     */
    var Song = Backbone.Model.extend({});

    var Detective_Details = Backbone.Model.extend({
        uuid: null,

        onChangeFinished: null,
        onLoadMoreFinished: null,

        tempDetective: false,
        tempParams: null,

        refetch: false,
        index: 0,

        initialize: function(){
            var fetchOld = this.fetch.bind(this);

            this.fetch = function(){
                fetchOld({xhrFields:{'withCredentials': true}, data: this.getParams(), type: 'POST', url: Constants.baseUrl});
            }.bind(this);

            this.bind('sync', function(){
                this.set("refetch", false);
                //Util.shuffle(this.get("detective_songs"));

                // Rearrange songs to play the ones we dont have listened to in a while
                if(typeof(window.localStorage) !== "undefined" && this.get("detective_songs").length > 0) {
                    var values = {},
                        keys = Object.keys(localStorage),
                        i = keys.length;

                    while ( i-- ) {
                        if ( keys[i].indexOf("s_") == 0 ){
                            if( parseInt(localStorage.getItem(keys[i])) <= Constants.currentTime - 5192000000 ){
                                window.localStorage.removeItem("s_" + keys[i]);
                                continue;
                            }

                            // Add a little randomization so we don't listen to the same order
                            values[keys[i]] = parseInt(localStorage.getItem(keys[i])) + Util.getRandomInt(-345600000, 345600000);
                        }
                    }

                    var maxLength = this.get("detective_songs").length;

                    this.get("detective_songs").forEach(function(el, idx){
                        if( values["s_" + el[0]] === undefined ){
                            values["s_" + el[0]] = idx / maxLength;
                        }
                    });

                    this.get("detective_songs").sort(function(a, b) {
                        var _a = (values["s_" + a[0]]) ? values["s_" + a[0]] : 0;
                        var _b = (values["s_" + b[0]]) ? values["s_" + b[0]] : 0;

                        return _a - _b;
                    });
                }

                this.index = ( this.get("detective_songs").length > 0 ) ? this.get("detective_songs")[0][0] : -1;

                if( this.onChangeFinished ){
                    this.onChangeFinished();
                }
            }.bind(this));
        },

        getParams: function(){
            if( !this.get("tempDetective") ){
                return "a=applydetective&uuid=" + this.get("uuid");
            }
            else{
                return "a=tempdetective&" + $.param(this.get("tempParams"));
            }
        },

        excludeSong: function(db_id){
            if( this.get("detective_info") && parseInt(this.get("detective_info").owner_id) != 0 ){
                // careful db_id != track_id
                // get track id
                var songsDetailed = this.get("detective_songs_detail");
                var songsDetailedIndex = songsDetailed.map(function(el){
                    return el.db_id;
                });
                var songs = this.get("detective_songs").map(function(el){
                    return el[0];
                });

                var idx = songsDetailedIndex.indexOf(db_id);

                if( idx > -1 ){
                    var toDelete = songsDetailed[idx].db_id;
                    var idxSongs = songs.indexOf(toDelete);

                    if( this.index === toDelete ){
                        // set index forward
                        if( idxSongs + 1 >= songs.length ){
                            this.index = songs[0];
                        }
                        else{
                            this.index = songs[idxSongs+1];
                        }
                    }

                    this.get("detective_songs").splice(idxSongs,1);
                    this.get("detective_songs_detail").splice(idx,1);
                }
            }
            else{
                Util.alert("You can't exclude songs from a public detective!");
            }
        },

        excludeArtist: function(artist_id){
            if( this.get("detective_info") && parseInt(this.get("detective_info").owner_id) != 0 ){
                var songsDetailed = this.get("detective_songs_detail");
                var songs = this.get("detective_songs").map(function(el){
                    return el[0];
                });
                var songsArtist = this.get("detective_songs").map(function(el){
                    return el[1];
                });

                var idx = songsArtist.indexOf(artist_id);

                while( idx >= 0 ){
                    var toDelete = songs[idx];

                    if( this.index === toDelete ){
                        // set index forward
                        if( idx + 1 >= songs.length ){
                            this.index = songs[0];
                        }
                        else{
                            this.index = songs[idx+1];
                        }
                    }

                    songs.splice(idx, 1);
                    songsArtist.splice(idx, 1);

                    this.get("detective_songs").splice(idx,1);
                    this.get("detective_songs_detail").splice(songsDetailed.indexOf(toDelete),1);

                    idx = songsArtist.indexOf(artist_id);
                }
            }
            else{
                Util.alert("You can't exclude songs from a public detective!");
            }
        },

        getDetailedSongs: function(displaySongs){
            var retArr = [];
            
            // get position
            var songsDetailed = this.get("detective_songs_detail");
            var songsDetailedSongIdIndex = songsDetailed.map(function(el){
                return el.db_id;
            });
            var songs = this.get("detective_songs").map(function(el){
                return el[0];
            });

            displaySongs.forEach(function(el){
                var idx = songsDetailedSongIdIndex.indexOf(el);

                if(idx >= 0){
                    var firstIdx = songsDetailedSongIdIndex.indexOf(songsDetailed[idx].db_id);

                    while( songsDetailedSongIdIndex.indexOf(songsDetailed[firstIdx].db_id, firstIdx+1) >= 0 ){
                        // We got duplicates
                        var _idx = songsDetailedSongIdIndex.indexOf(songsDetailed[firstIdx].db_id, firstIdx+1);
                        var _track_id = songsDetailed[_idx].db_id;

                        this.get("detective_songs").splice(songs.indexOf(_track_id), 1);
                        this.get("detective_songs_detail").splice(_idx, 1);

                        songsDetailed = this.get("detective_songs_detail");
                        songsDetailedSongIdIndex = songsDetailed.map(function(el){
                            return el.db_id;
                        });
                        songs = this.get("detective_songs").map(function(el){
                            return el[0];
                        });
                    }

                    if ( firstIdx == idx ){
                        retArr.push(songsDetailed[idx]);
                    }
                }
            }.bind(this));

            return retArr;
        },

        getNextSongs: function(numDisplayed, playNext){
            if( this.index == -1 ){
                if( this.onLoadMoreFinished ){
                    this.onLoadMoreFinished([], playNext);
                }

                return;
            }

            if( !numDisplayed ){
                numDisplayed = 0;
            }

            // get position
            var songsDetailed = this.get("detective_songs_detail");
            var songsDetailedIndex = songsDetailed.map(function(el){
                return el.db_id;
            });
            var songs = this.get("detective_songs").map(function(el){
                return el[0];
            });

            // We already displayed all songs
            if( songs.length === 0 || (numDisplayed !== undefined && songs.length <= numDisplayed) ){
                if( this.onLoadMoreFinished ){
                    this.onLoadMoreFinished([], playNext);
                }

                return;
            }

            var neededDetails = [];
            var displaySongs = [];

            var idx = songs[songs.indexOf(this.index)];

            while( displaySongs.length < 30 ){
                var pos = songs.indexOf(idx);

                if( displaySongs.indexOf(songs[pos]) > -1 ){
                    break;
                }

                displaySongs.push(songs[pos]);

                if( songsDetailedIndex.indexOf(idx) == -1 ){
                    neededDetails.push(songs[pos]);
                }

                if( numDisplayed + displaySongs.length >= songs.length ){
                    break;
                }
                else{
                    pos = (pos + 1 < songs.length) ? pos + 1 : 0;
                    idx = songs[pos];
                }
            }

            // Reassign index
            this.index = idx;

            if( neededDetails.length > 0 ){
                // Load them
                $.ajax({
                    url: Constants.baseUrl,
                    type: "POST",
                    data: "a=getsongdetails&ids=" + neededDetails.join(","),
                }).done(function(data){
                    var obj = JSON.parse(data);

                    obj.forEach(function(el){
                        songsDetailed.push(el);
                    });

                    if( this.onLoadMoreFinished ){
                        this.onLoadMoreFinished(this.getDetailedSongs(displaySongs), playNext);
                    }
                }.bind(this));
            }
            else if( this.onLoadMoreFinished ){
                this.onLoadMoreFinished(this.getDetailedSongs(displaySongs), playNext);
            }
        }
    });

    var GlobalCollections = {
        detectives: new (Backbone.Collection.extend({
            url: Constants.baseUrl + "?a=getdetectives"
        }))(),

        detectives_detail: new (Backbone.Collection.extend({
            model: Detective_Details
        }))(),

        ytSongCache: []
    };

    /**
     * Our Global EventPipe
     */
    var EventPipe = _.extend({}, Backbone.Events);

    // Set Global Reference
    window.EventPipe = EventPipe;

    /**
     * User
     */
    var User = {
        spotifyDetails: {
            redirectUrl: "http://play.sounddetective.net/token.php",
            clientId: "51c70131fec1488580a605fa2c27a70c",
            scopes: ['user-read-email', 'playlist-read-private', 'playlist-modify-private']
        },

        facebookDetails: {
            clientId: "941196972630314"
        },

        init: function(){
            // Global Events
            require(["js/md5.js"], function(){
                EventPipe.on("User-loginNormal", function(){
                    this.loginNormal();
                }.bind(this));
            }.bind(this));

            EventPipe.on("User-loginSpotify", function(){
                this.loginSpotify();
            }.bind(this));

            EventPipe.on("User-register", function(){
                EventPipe.trigger(Constants.contentView + "-showRegisterView");
            });

            require(["FB"], function(FB) {
                EventPipe.on("User-loginFacebook", function(){
                    this.loginFacebook();
                }.bind(this));
            }.bind(this));

            // Create IE + others compatible event handler
            var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
            var eventer = window[eventMethod];
            var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

            // Listen to message from child window
            eventer(messageEvent,function(e) {
                if( e.data.type == "spotifyLogin" ){
                   this.spotifyCallback(e.data.data);
                }
                else{
                    console.log('Received Message!:  ', e.data);
                }
            }.bind(this), false);

            return this;
        },

        logout: function(){
            $.ajax({
                url: Constants.baseUrl + "?a=logout",
                xhrFields:{'withCredentials': true}
            })
            .done(function( data ) {
                window.location.reload();
            });
        },

        createSpotifyPlaylist: function(songs, uuid){
            this.refreshSpotifyToken();

            Util.playlistPrompt("Enter Playlist Name...", function(name, excludeSongs){
                if( !name ){
                    return;
                }

                Util.showLoadingIndicator();
                name = name.replace(/[^\w\s]/gi, '');

                if( !name ){
                    return;
                }

                if( excludeSongs ) {
                    $.ajax({
                        url: Constants.baseUrl,
                        type: "POST",
                        data: "a=massexclude&uuid=" + uuid + "&songs=" + songs.map(function(el){
                            return el.db_id;
                        }).join(","),
                        xhrFields:{'withCredentials': true}
                    });
                }

                $.ajax({
                    url: 'https://api.spotify.com/v1/users/' + localStorage.getItem("spotifyUserId") + '/playlists',
                    type: "POST",
                    data: "{\"name\":\"" + name + "\", \"public\":false}",
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem("spotifyToken"),
                        'Content-Type': 'application/json'
                    }
                }).done(function(data){
                    // success
                    if( data.id ){
                        var playlistId = data.id;

                        for(var i=0;i<songs.length;i=i+100){
                            var songsForPlaylist = songs.map(function(el){
                                return el.available_spotify_id;
                            }).splice(i,100);

                            if(typeof(window.localStorage) !== "undefined") {
                                songs.map(function(el){
                                    return el.db_id;
                                }).splice(i,100).forEach(function(id){
                                    window.localStorage.setItem("s_" + id, new Date().getTime());
                                });
                            }

                            /*
                             * POST https://api.spotify.com/v1/users/{user_id}/playlists/{playlist_id}/tracks
                             */
                            var obj = $.ajax({
                                url: 'https://api.spotify.com/v1/users/' + localStorage.getItem("spotifyUserId") + '/playlists/' + data.id + "/tracks",
                                type: "POST",
                                data: '{"uris": ["spotify:track:' + songsForPlaylist.join('","spotify:track:') + '"]}',
                                headers: {
                                    'Authorization': 'Bearer ' + localStorage.getItem("spotifyToken"),
                                    'Content-Type': 'application/json'
                                }
                            });

                            if( i+100 >= songs.length ){
                                obj.done(function(data){
                                    if( excludeSongs ){
                                        window.setTimeout(function(){
                                            console.info(data);
                                            Util.hideLoadingIndicator();
                                            Util.alert('Playlist successful created!');

                                            window._router.applyDetective(uuid);
                                        }.bind(this), 1500);
                                    }
                                    else{
                                        console.info(data);
                                        Util.hideLoadingIndicator();
                                        Util.alert('Playlist successful created!');
                                    }
                                }.bind(this));
                            }
                        }
                    }
                    else{
                        Util.hideLoadingIndicator();
                        Util.alert('Error creating Playlist!');
                        console.error(data);
                    }
                }.bind(this));
            }.bind(this));
        },

        refreshSpotifyToken: function(){
            if( parseInt(localStorage.getItem("spotifyTokenExpires")) <= new Date().getTime() ){
                var res = $.ajax({
                    url: Constants.baseUrl + '?a=refresh_token',
                    async: false,
                    xhrFields:{'withCredentials': true}
                }).responseText;

                if( res ){
                    localStorage.setItem("spotifyToken", res);
                    localStorage.setItem("spotifyTokenExpires", new Date(new Date().getTime() + 3550000).getTime());
                }

                if( !localStorage.getItem("spotifyToken") ){
                    Util.alert('Error with refreshing token!');
                    throw "Error with refreshing token!";
                    return;
                }
            }
        },

        spotifyCallback: function(token){
            if( localStorage ){
                Util.showLoadingIndicator();

                // Now login in SoundDetective
                $.ajax({
                    url: 'https://api.spotify.com/v1/me',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    }
                }).done(function(data){
                    if(data.id){
                        var spotify_user_id = data.id;
                        var spotify_name = (data.display_name) ? data.display_name : data.id;

                        $.ajax({
                            url: Constants.baseUrl + '?a=login&t=spotify&name=' + encodeURIComponent(spotify_name) + "&id=" + encodeURIComponent(spotify_user_id) + "&access_token=" + encodeURIComponent(token),
                            xhrFields:{'withCredentials': true}
                        }).done(function(data){
                            localStorage.setItem("spotifyToken", token);
                            localStorage.setItem("spotifyTokenExpires", new Date(new Date().getTime() + 3550000).getTime());
                            localStorage.setItem("spotifyUserId", spotify_user_id);

                            window.location.reload();
                        });
                    }
                    else{
                        Util.hideLoadingIndicator();
                        Util.alert("Login failed!");
                    }
                });
            }
        },

        facebookCallback: function(response){
            Util.showLoadingIndicator();
            var token = response.authResponse.accessToken;

            FB.api('/me', function(data) {
                if(data.id){
                    var facebook_id = data.id;
                    var facebook_name = (data.name) ? data.name : data.id;

                    $.ajax({
                        url: Constants.baseUrl + '?a=login&t=facebook&name=' + encodeURIComponent(facebook_name) + "&id=" + encodeURIComponent(facebook_id) + "&access_token=" + encodeURIComponent(token),
                        xhrFields:{'withCredentials': true}
                    }).done(function(data){
                        localStorage.setItem("facebookToken", token);
                        localStorage.setItem("facebookUserId", facebook_id);

                        window.location.reload();
                    });
                }
                else{
                    Util.alert("Login failed!");
                }
            });
        },

        loginNormal: function(){
            var data = "a=login&t=normal&name=" + $(".login-username").val() + "&password=" + CryptoJS.MD5($(".login-password").val()).toString();

            $.ajax({
                url: Constants.baseUrl,
                method: "POST",
                data: data,
                xhrFields:{'withCredentials': true}
            }).done(function(response){
                if( response && response.length ){
                    Util.alert(response);
                }
                else{
                    $('#loginForm').submit();
                }
            });
        },

        loginFacebook: function(){
            if( localStorage ){
                FB.init({
                    appId      : this.facebookDetails.clientId,
                    xfbml      : true,
                    version    : 'v2.5'
                });

                FB.login(function(response) {
                    this.facebookCallback(response);
                }.bind(this), {scope: 'public_profile'});
            }
        },

        loginSpotify: function(){
            var url = 'https://accounts.spotify.com/authorize?client_id=' + this.spotifyDetails.clientId +
                '&redirect_uri=' + encodeURIComponent(this.spotifyDetails.redirectUrl) +
                '&scope=' + encodeURIComponent(this.spotifyDetails.scopes.join(" ")) +
                '&response_type=code';

            var width = 450,
                height = 730,
                left = (screen.width / 2) - (width / 2),
                top = (screen.height / 2) - (height / 2);

            var w = window.open(url,
                'Spotify',
                'menubar=no,location=no,resizable=no,scrollbars=no,status=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left
            );
        }
    }.init();

    /**
     * Modify Detectives View
     * @type {void|*}
     */
    var ModifyDetectivesView = Backbone.View.extend({
        templateId: "ModifyDetectivesTemplate",
        sid: Constants.modifyDetectivesView,

        editMode: false,
        editModel: new Backbone.Model,

        searchArtists: new Backbone.Collection,

        excludeSongs: new Backbone.Collection,
        excludeSongsLoaded: false,

        excludeArtists: new Backbone.Collection,
        excludeArtistsLoaded: false,

        currentStep: null,

        minRelDate: 0,
        maxRelDate: 1000,

        popularArtists: new (Backbone.Collection.extend({
            url: Constants.baseUrl + "?a=getpopularartists"
        }))(),

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;

            this.registerEvents();
        },

        displayTotalArtists: function(){
            this.$artistTotal.html("Loading...");
            var str = [];

            this.searchArtists.toJSON().forEach(function(el){
                str.push(el.id + "," + el.distance);
            });

            str = str.join(";");

            if( str.length > 0 ){
                $.ajax({
                    url: Constants.baseUrl + "?a=getdetectiveamount&artists=" + encodeURIComponent(str),
                    method: "GET",
                    xhrFields:{'withCredentials': true}
                }).done(function(data){
                    this.$artistTotal.html(data);
                }.bind(this));
            }
            else{
                this.$artistTotal.html("?");
            }
        },

        assignHandlers: function(){
            this.searchArtists.on("change update", function(){
                this.displayTotalArtists();
            }.bind(this));

            this.$.find(".mdt-exclude-artists-filter").on('input', function(e){
                this.renderExcludedArtists($(e.currentTarget).val());
            }.bind(this));

            this.$.find(".mdt-exclude-songs-filter").on('input', function(e){
                this.renderExcludedSongs($(e.currentTarget).val());
            }.bind(this));

            this.$.find(".mdt-delete-detective").click(function(){
                Util.showLoadingIndicator();

                var editModel = this.editModel.toJSON();

                // Check if temp detective
                if( parseInt(editModel.owner_id) == -1337 ){
                    var tempDetectives = (localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];
                    var idx = tempDetectives.map(function(el){
                        return el.uuid;
                    }).indexOf(editModel.uuid);

                    tempDetectives.splice(idx, 1);

                    localStorage.setItem("tempDetectives", JSON.stringify(tempDetectives));
                    EventPipe.trigger(Constants.contentView + "-showDetectivesView");

                    GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    $.ajax({
                        xhrFields:{'withCredentials': true},
                        url: Constants.baseUrl + "?a=deletedetective&uuid=" + editModel.uuid
                    }).done(function(data){
                        Util.hideLoadingIndicator();
                        EventPipe.trigger(Constants.contentView + "-showDetectivesView");

                        Util.alert("Succesful Deleted!");
                        GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});
                    });
                }
            }.bind(this)).qtip({
                content: {
                    text: "Delete Detective"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".mdt-export-btn").click(function(){
                vex.open({
                    content: this.exportPopupTemplate({uuid: this.editMode}),
                    afterOpen: function($vexContent) {
                        $vexContent.find(".mdt-export-btn").click(function(){
                            vex.close($vexContent.data().vex.id);
                        });

                        return;
                    }.bind(this),
                    afterClose: function() {
                        return;
                    }.bind(this),
                    showCloseButton: false
                });
            }.bind(this)).qtip({
                content: {
                    text: "Export Detective"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".mdt-clear-excluded-artists").click(function(){
                this.excludeArtists.reset();
                this.renderExcludedArtists();
            }.bind(this)).qtip({
                content: {
                    text: "Empty Table"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".mdt-clear-excluded-songs").click(function(){
                this.excludeSongs.reset();
                this.renderExcludedSongs();
            }.bind(this)).qtip({
                content: {
                    text: "Empty Table"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            require(["chosen.jquery.min"], function(){
                this.$.find(".mdt-import-settings").click(function(){
                    this.createImportPopup({detectives: GlobalCollections.detectives.toJSON().map(function(el){
                        if(parseInt(el.owner_id) == 0){
                            return null;
                        }

                        return el;
                    }), public_detectives: GlobalCollections.detectives.toJSON().map(function(el){
                        if(parseInt(el.owner_id) == 0){
                            return el;
                        }

                        return null;
                    })}, this.importSettings.bind(this));
                }.bind(this)).qtip({
                    content: {
                        text: "Import From Another Detective"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                this.$.find(".mdt-import-excluded-artists").click(function(){
                    this.createImportPopup({detectives: GlobalCollections.detectives.toJSON().map(function(el){
                        if(parseInt(el.owner_id) == 0){
                            return null;
                        }

                        return el;
                    })}, this.importExcludedArtists.bind(this));
                }.bind(this)).qtip({
                    content: {
                        text: "Import From Another Detective"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                this.$.find(".mdt-import-excluded-songs").click(function(){
                    this.createImportPopup({detectives: GlobalCollections.detectives.toJSON().map(function(el){
                        if(parseInt(el.owner_id) == 0){
                            return null;
                        }

                        return el;
                    })}, this.importExcludedSongs.bind(this));
                }.bind(this)).qtip({
                    content: {
                        text: "Import From Another Detective"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });
            }.bind(this));

            this.$.find(".mdt-bar-step-1").click(function(){
                this.currentStep = 0;
                this.showCurrentStep();
            }.bind(this));

            this.$.find(".mdt-bar-step-2").click(function(){
                this.currentStep = 1;
                this.showCurrentStep();
            }.bind(this));

            this.$.find(".mdt-bar-step-3").click(function(){
                if( logged_in ){
                    this.currentStep = 2;
                    this.showCurrentStep();
                }
                else{
                    Util.alert("Please log in to exclude songs!");
                }
            }.bind(this));

            this.$.find(".mdt-bar-step-4").click(function(){
                if( logged_in ){
                    this.currentStep = 3;
                    this.showCurrentStep();
                }
                else{
                    Util.alert("Please log in to exclude songs!");
                }
            }.bind(this));

            this.$.find(".mdt-abort").click(function(){
                if( this.lastView == Constants.showDetectivesView ){
                    EventPipe.trigger(Constants.contentView + "-showDetectivesView");
                }
                else if (this.lastView == Constants.exploreView ){
                    EventPipe.trigger(Constants.contentView + "-showExploreView");
                }

            }.bind(this));

            this.$.find(".mdt-save").click(function(){
                // Collect the settings
                var params = {};

                if( this.editMode ){
                    params.uuid = this.editMode;
                    params.image = this.editModel.get("image");
                }

                params.name = this.$.find(".detectiveName").val();

                if( params.name.length > 18 || params.name.length == 0 ){
                    Util.alert("Please choose a name between 1 and 18 characters!");
                    return;
                }

                params.artists = [];

                if( this.searchArtists.toJSON().length > 8 ){
                    Util.alert("Only up to 8 artists allowed!");
                    return;
                }

                this.searchArtists.toJSON().forEach(function(el){
                    params.artists.push(el.id + "," + el.distance);
                });

                params.artists = params.artists.join(";");

                if( params.artists.length == 0 ){
                    params.artists = "";
                }

                var song_popularity = this.$songPopSlider.val();

                song_popularity[0] = Util.getNumberFromIndex(song_popularity[0], Util.songPopularity);
                song_popularity[1] = Util.getUpperNumberFromIndex(song_popularity[1], Util.songPopularity);

                if( song_popularity[0] > 0 ){
                    params.mnsp = song_popularity[0];
                }

                if( song_popularity[1] < 100 ){
                    params.mxsp = song_popularity[1];
                }

                var artist_popularity = this.$artistPopSlider.val();

                artist_popularity[0] = Util.getNumberFromIndex(artist_popularity[0], Util.artistPopularity);
                artist_popularity[1] = Util.getUpperNumberFromIndex(artist_popularity[1], Util.artistPopularity);

                if( artist_popularity[0] > 0 ){
                    params.mnap = artist_popularity[0];
                }

                if( artist_popularity[1] < 100 ){
                    params.mxap = artist_popularity[1];
                }

                var release_date = this.$releaseDateSlider.val();

                if( release_date[0] > this.minRelDate ){
                    params.mnrd = parseInt(Util.linear_interpolation(release_date[0], 0, 0, 1000, Constants.currentTime)/1000);
                }

                if( release_date[1] < this.maxRelDate ){
                    params.mxrd = parseInt(Util.linear_interpolation(release_date[1], 0, 0, 1000, Constants.currentTime)/1000);
                }

                if( this.$excludeRemixChkbx.prop("checked") ){
                    params.exr = 1;
                }
                if( this.$excludeAcousticChkbx.prop("checked") ){
                    params.exa = 1;
                }
                if( this.$excludeCollectionChkbx.prop("checked") ){
                    params.exc = 1;
                }

                if( this.excludeArtistsLoaded ){
                    params.exar = [];

                    this.excludeArtists.toJSON().forEach(function(el){
                        params.exar.push(el.id);
                    }.bind(this));

                    params.exar = params.exar.join(",");

                    if( params.exar.length == 0 ){
                        params.exar = "false";
                    }
                }

                if( this.excludeSongsLoaded ){
                    params.exs = [];

                    this.excludeSongs.toJSON().forEach(function(el){
                        params.exs.push(el.id);
                    }.bind(this));

                    params.exs = params.exs.join(",");

                    if( params.exs.length == 0 ){
                        params.exs = "false";
                    }
                }

                if( logged_in ){
                    Util.showLoadingIndicator();

                    $.ajax({
                        url: Constants.baseUrl,
                        method: "POST",
                        data: "a=createdetective&" + $.param(params),
                        xhrFields:{'withCredentials': true}
                    }).done(function(data){
                        if( data.length != 13 ){
                            alert("Error!");
                        }
                        else{
                            GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});

                            if( params.uuid && GlobalCollections.detectives_detail.findWhere({uuid: params.uuid}) ){
                                GlobalCollections.detectives_detail.findWhere({uuid: params.uuid}).set("refetch", true);
                            }

                            // Excute Detective
                            EventPipe.trigger(Constants.contentView + "-showExploreView");
                            EventPipe.trigger(Constants.exploreView + "-exploreDetective", {uuid: data, name: params.name, image: (this.editMode) ? params.image : Constants.imageUrl}, true);
                        }
                    }.bind(this));
                }
                // save as temporary detective
                else{
                    if( !params.name ){
                        Util.alert("Please enter a detective name!");
                        return;
                    }

                    var tempDetectives = (localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];

                    if( params.uuid ){
                        var idx = tempDetectives.map(function(el){
                            return el.uuid;
                        }).indexOf(params.uuid);

                        if( idx < 0 ){
                            params.uuid = Util.guid();
                            params.artist_model = this.searchArtists.toJSON();
                            tempDetectives.push(params);
                        }
                        else{
                            params.artist_model = this.searchArtists.toJSON();
                            tempDetectives[idx] = params;
                        }
                    }
                    else{
                        params.uuid = Util.guid();
                        params.artist_model = this.searchArtists.toJSON();
                        tempDetectives.push(params);
                    }

                    localStorage.setItem("tempDetectives", JSON.stringify(tempDetectives));

                    if( params.uuid && GlobalCollections.detectives_detail.findWhere({uuid: params.uuid}) ){
                        GlobalCollections.detectives_detail.findWhere({uuid: params.uuid}).set("refetch", true);
                    }

                    EventPipe.trigger(Constants.contentView + "-showExploreView");
                    EventPipe.trigger(Constants.exploreView + "-exploreDetective", {uuid: params.uuid, temp: params, name: params.name, image: Constants.imageUrl}, true);
                    EventPipe.trigger(Constants.showDetectivesView + "-updateDetectives");
                }
            }.bind(this));

            require(["jquery.auto-complete.min"], function(){
                var artist_autocomplete_xhr = null;
                var artist_autocomplete_json = null;

                this.$searchArtistsInput.autoComplete({
                    delay: 200,
                    minChars: 2,
                    source: function(term, response){
                        term = term.toLowerCase();

                        try { artist_autocomplete_xhr.abort(); } catch(e){}

                        artist_autocomplete_xhr = $.ajax({
                            xhrFields:{'withCredentials': true},
                            url: Constants.baseUrl + "?a=autocomplete&name=" + encodeURIComponent(term),
                        })
                            .done(function(res){
                                artist_autocomplete_json = JSON.parse(res);
                                response(artist_autocomplete_json.map(function(elem){
                                    return elem.name;
                                }));
                            });
                    },
                    onSelect: function(event, term, item) {
                        var idx = artist_autocomplete_json.map(function(elem){
                            return elem.name;
                        }).indexOf(term);

                        if( idx >= 0 ){
                            if( this.searchArtists.toJSON().length >= 8 ){
                                Util.alert("Only up to 8 artists allowed!");
                                return;
                            }

                            if( artist_autocomplete_json[idx].id ){
                                this.searchArtists.add({id: artist_autocomplete_json[idx].id, name: artist_autocomplete_json[idx].name, distance: 0.25, image_small: (artist_autocomplete_json[idx].image_small) ? artist_autocomplete_json[idx].image_small : null});
                                this.renderArtists();

                                this.$searchArtistsInput.val("");
                            }
                        }

                        this.$searchArtistsInput.val("");
                    }.bind(this)
                }).keypress(function (ev) {
                    var keycode = (ev.keyCode ? ev.keyCode : ev.which);
                    if (keycode.toString() === '13') {
                        $.ajax({
                            xhrFields:{'withCredentials': true},
                            url: Constants.baseUrl + "?a=autocomplete&name=" + encodeURIComponent(this.$searchArtistsInput.val()),
                        }).done(function(res){
                            artist_autocomplete_json = JSON.parse(res);

                            if( artist_autocomplete_json.length > 0 ){
                                if( this.searchArtists.toJSON().length >= 8 ){
                                    Util.alert("Only up to 8 artists allowed!");
                                    return;
                                }

                                if( artist_autocomplete_json[0].id ){
                                    this.searchArtists.add({id: artist_autocomplete_json[0].id, name: artist_autocomplete_json[0].name, distance: 0.25, image_small: (artist_autocomplete_json[0].image_small) ? artist_autocomplete_json[0].image_small : null});
                                    this.renderArtists();

                                    this.$searchArtistsInput.val("");
                                }
                            }
                            else{
                                Util.alert("Artist couldn't be found!");
                            }
                        }.bind(this));
                    }
                }.bind(this));

                this.$.find(".mdt-exclude-artist-input").autoComplete({
                    delay: 300,
                    minChars: 2,
                    source: function(term, response){
                        term = term.toLowerCase();

                        try { artist_autocomplete_xhr.abort(); } catch(e){}

                        artist_autocomplete_xhr = $.ajax({
                            xhrFields:{'withCredentials': true},
                            url: Constants.baseUrl + "?a=autocomplete&name=" + encodeURIComponent(term),
                        })
                        .done(function(res){
                            artist_autocomplete_json = JSON.parse(res);
                            response(artist_autocomplete_json.map(function(elem){
                                return elem.name;
                            }));
                        });
                    },
                    onSelect: function(event, term, item) {
                        var idx = artist_autocomplete_json.map(function(elem){
                            return elem.name;
                        }).indexOf(term);

                        if( idx >= 0 ){
                            if( artist_autocomplete_json[idx].id ){
                                this.excludeArtists.add({id: artist_autocomplete_json[idx].id, name: artist_autocomplete_json[idx].name, image_small: (artist_autocomplete_json[idx].image_small) ? artist_autocomplete_json[idx].image_small : null});
                                this.renderExcludedArtists();
                            }
                        }

                        this.$.find(".mdt-exclude-artist-input").val("");
                    }.bind(this)
                });
            }.bind(this));

            this.$releaseDateSlider.noUiSlider({
                start: [ this.minRelDate, this.maxRelDate ],
                connect: true,
                step: 50,
                range: {
                    'min': this.minRelDate,
                    '25%': 500,
                    '50%': 700,
                    '75%': 950,
                    'max': this.maxRelDate
                }
            });

            this.$releaseDateSlider.find(".noUi-handle-lower").append("<span>");
            this.$releaseDateSlider.find(".noUi-handle-upper").append("<span>");

            this.$releaseDateSlider.Link('lower').to(function(value, e){
                var val = this.$releaseDateSlider.val();

                this.$releaseDateSlider.find(".noUi-handle-lower > span").html(new Date(parseInt(Util.linear_interpolation(val[0], 0, 0, 1000, Constants.currentTime))).toLocaleDateString());
            }.bind(this));

            this.$releaseDateSlider.Link('upper').to(function(value, e){
                var val = this.$releaseDateSlider.val();

                this.$releaseDateSlider.find(".noUi-handle-upper > span").html(new Date(parseInt(Util.linear_interpolation(val[1], 0, 0, 1000, Constants.currentTime))).toLocaleDateString());
            }.bind(this));

            this.$songPopSlider.noUiSlider({
                start: [ 3, (Util.songPopularity.length-1) ],
                connect: true,
                step: 1,
                range: {
                    'min': 0,
                    'max': (Util.songPopularity.length-1)
                }
            });

            this.$songPopSlider.find(".noUi-handle-lower").append("<span>");
            this.$songPopSlider.find(".noUi-handle-upper").append("<span>");

            this.$songPopSlider.Link('lower').to(function(value, e){
                var val = this.$songPopSlider.val();

                this.$songPopSlider.find(".noUi-handle-lower > span").html(Util.getStringFromIndex(val[0], Util.songPopularity));
            }.bind(this));

            this.$songPopSlider.Link('upper').to(function(value, e){
                var val = this.$songPopSlider.val();

                this.$songPopSlider.find(".noUi-handle-upper > span").html(Util.getStringFromIndex(val[1], Util.songPopularity));
            }.bind(this));

            this.$artistPopSlider.noUiSlider({
                start: [ 0, (Util.artistPopularity.length-1) ],
                connect: true,
                step: 1,
                range: {
                    'min': 0,
                    'max': (Util.artistPopularity.length-1)
                }
            });

            this.$artistPopSlider.find(".noUi-handle-lower").append("<span>");
            this.$artistPopSlider.find(".noUi-handle-upper").append("<span>");

            this.$artistPopSlider.Link('lower').to(function(value, e){
                var val = this.$artistPopSlider.val();

                this.$artistPopSlider.find(".noUi-handle-lower > span").html(Util.getStringFromIndex(val[0], Util.artistPopularity));
            }.bind(this));

            this.$artistPopSlider.Link('upper').to(function(value, e){
                var val = this.$artistPopSlider.val();

                this.$artistPopSlider.find(".noUi-handle-upper > span").html(Util.getStringFromIndex(val[1], Util.artistPopularity));
            }.bind(this));
        },

        createImportPopup: function(obj, fnCallback){
            vex.open({
                content: this.importPopupTemplate(obj),
                afterOpen: function($vexContent) {
                    var changed = false;

                    $vexContent.find(".mdt-import-select").on("change", function(){
                        changed = true;
                    }).chosen({
                        width: "100%"
                    });

                    $vexContent.find(".mdt-popup-import").click(function(){
                        //if( $vexContent.find(".mdt-import-select option:selected").val() ){
                        if( changed && $vexContent.find(".mdt-import-select option:selected").val() ){
                            fnCallback($vexContent.find(".mdt-import-select option:selected").val());
                            vex.close($vexContent.data().vex.id);
                        }
                        else{
                            if( $vexContent.find(".mdt-import-input").val() ){
                                fnCallback($vexContent.find(".mdt-import-input").val());
                                vex.close($vexContent.data().vex.id);
                            }
                            else{
                                Util.alert("Please select a detective!");
                            }
                        }
                    }.bind(this));

                    $vexContent.find(".mdt-popup-cancel").click(function(){
                        vex.close($vexContent.data().vex.id);
                    });

                    return;
                }.bind(this),
                afterClose: function() {
                    return;
                }.bind(this),
                showCloseButton: false
            });
        },

        registerEvents: function(){
            this.popularArtists.on("sync", function(){
                this.renderPopularArtists();
            }.bind(this));

            this.editModel.on("sync", function(){
                Util.hideLoadingIndicator();
                Util.finishLoadingSlider();
                this.editDetective();
            }.bind(this));

            this.excludeSongs.on("sync", function(){
                Util.hideLoadingIndicator();
                Util.finishLoadingSlider();
                this.renderExcludedSongs();
            }.bind(this));

            this.excludeArtists.on("sync", function(){
                Util.hideLoadingIndicator();
                Util.finishLoadingSlider();
                this.renderExcludedArtists();
            }.bind(this));
        },

        renderPopularArtists: function(){
            var $popularArtists = this.$.find(".mdt-popular-artists");

            this.popularArtists.toJSON().forEach(function(el){
                var a = document.createElement("a");

                a.innerHTML = el.name;
                a.href = "javascript:void(0)";

                $(a).click(function(){
                    if( this.searchArtists.toJSON().length >= 8 ){
                        Util.alert("Only up to 8 artists allowed!");
                        return;
                    }

                    this.searchArtists.add({id: el.id, name: el.name, distance: 0.25, image_small: (el.image_small) ? el.image_small : null});
                    this.renderArtists();
                }.bind(this));

                $popularArtists.append(a);
            }.bind(this));
        },

        renderExcludedSongs: function(filter){
            this.$excludeSongs.html("");

            if( this.excludeSongs.length ){
                this.excludeSongs.toJSON().forEach(function(el, idx){
                    el.idx = idx;

                    if( filter ){
                        if( (el.artist + el.title).toLowerCase().indexOf(filter.toLowerCase()) == -1 ){
                            return;
                        }
                    }
                    
                    el.artist_name = el.artist.split(";;")[0];

                    this.$excludeSongs.append(this.exSongTemplate(el));
                }.bind(this));

                this.$excludeSongs.find(".mdt-song-exclude-delete").click(function(e){
                    this.excludeSongs.remove(this.excludeSongs.at(e.currentTarget.dataset["idx"]));
                    this.renderExcludedSongs();
                }.bind(this));
            }
            else{
                this.$excludeSongs.append(this.artistTemplateEmpty({col_span: 3,message:"No songs excluded!"}));
            }
        },

        renderExcludedArtists: function(filter){
            this.$excludeArtists.html("");

            if( this.excludeArtists.length ){
                this.excludeArtists.toJSON().forEach(function(el, idx){
                    if( !el.image_small ){
                        el.image_small = Constants.smallImageNotFoundUrl;
                    }

                    el.idx = idx;

                    if( filter ){
                        if( (el.name).toLowerCase().indexOf(filter.toLowerCase()) == -1 ){
                            return;
                        }
                    }

                    this.$excludeArtists.append(this.exArtistTemplate(el));
                }.bind(this));

                this.$excludeArtists.find(".mdt-artist-exclude-delete").click(function(e){
                    this.excludeArtists.remove(this.excludeArtists.at(e.currentTarget.dataset["idx"]));
                    this.renderExcludedArtists();
                }.bind(this));

                this.$excludeArtists.find(".mdt-artist-name").click(function(e){
                    EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["id"]);
                });
            }
            else{
                this.$excludeArtists.append(this.artistTemplateEmpty({col_span: 2,message:"No artists excluded!"}));
            }
        },

        editDetective: function(){
            this.$.find(".detectiveImage").attr("src",this.editModel.get("image"));
            this.$.find(".detectiveName").val(this.editModel.get("name"));

            this.searchArtists.reset();

            var editModel = this.editModel.toJSON();

            if( editModel.owner_id !== undefined && parseInt(editModel.owner_id) !== 0 ){
                this.$deleteDetective.show();
            }

            // Off or it would recalc very often
            this.searchArtists.off("change update");

            editModel["artists"].forEach(function(el){
                this.searchArtists.add(el);
            }.bind(this));

            this.displayTotalArtists();
            this.renderArtists();
            this.applyAdvancedSettings(editModel);

            this.searchArtists.on("change update", function(){
                this.displayTotalArtists();
            }.bind(this));
        },

        resetAdvancedSettings: function(){
            this.$songPopSlider.val([5,(Util.songPopularity.length-1)]);
            this.$artistPopSlider.val([0, (Util.artistPopularity.length-1)]);
            this.$releaseDateSlider.val([this.minRelDate, this.maxRelDate]);

            this.$excludeRemixChkbx.prop("checked", false);
            this.$excludeAcousticChkbx.prop("checked", false);
            this.$excludeCollectionChkbx.prop("checked", false);
        },

        applyAdvancedSettings: function(obj){
            var song_pop = [0, (Util.songPopularity.length-1)];

            if( obj.min_song_pop ){
              song_pop[0] = Util.getIndexFromNumber(obj.min_song_pop, Util.songPopularity);
            }
            if( obj.max_song_pop ){
              song_pop[1] = Util.getIndexFromNumber(obj.max_song_pop, Util.songPopularity);
            }

            this.$songPopSlider.val(song_pop);

            var artist_pop = [0, (Util.artistPopularity.length-1)];

            if( obj.min_artist_pop ){
                artist_pop[0] = Util.getIndexFromNumber(obj.min_artist_pop, Util.artistPopularity);
            }
            if( obj.max_artist_pop ){
                artist_pop[1] = Util.getIndexFromNumber(obj.max_artist_pop, Util.artistPopularity);
            }

            this.$artistPopSlider.val(artist_pop);

            var release_date = [this.minRelDate, this.maxRelDate];

            if( obj.min_release_date ){
                release_date[0] = parseInt(Util.linear_interpolation(obj.min_release_date, 0, 0, Constants.currentTime/1000, 1000));
            }
            if( obj.max_release_date ){
                release_date[1] = parseInt(Util.linear_interpolation(obj.max_release_date, 0, 0, Constants.currentTime/1000, 1000));
            }

            this.$releaseDateSlider.val(release_date);

            if( obj.exclude_remix && parseInt(obj.exclude_remix) == 1 ){
                this.$excludeRemixChkbx.prop("checked", true);
            }
            else{
                this.$excludeRemixChkbx.prop("checked", false);
            }

            if( obj.exclude_acoustic && parseInt(obj.exclude_acoustic) == 1 ){
                this.$excludeAcousticChkbx.prop("checked", true);
            }
            else{
                this.$excludeAcousticChkbx.prop("checked", false);
            }

            if( obj.exclude_collection && parseInt(obj.exclude_collection) == 1 ){
                this.$excludeCollectionChkbx.prop("checked", true);
            }
            else{
                this.$excludeCollectionChkbx.prop("checked", false);
            }
        },

        renderArtists: function(){
            this.$searchArtists.html("");

            if( this.searchArtists.length > 0 ){
                this.searchArtists.forEach(function(el, idx){
                    el = el.toJSON();
                    el.idx = idx;

                    if( !el.image_small ){
                        el.image_small = Constants.smallImageNotFoundUrl;
                    }

                    this.$searchArtists.append(this.artistTemplate(el));
                }.bind(this));

                this.$searchArtists.find(".mdt-artist-remove-btn").click(function(e){
                    var idx = e.currentTarget.dataset["idx"];

                    this.searchArtists.remove(this.searchArtists.at(idx));
                    this.renderArtists();
                }.bind(this));

                this.$searchArtists.find(".edgeSpinner").each(function(idx, el){
                    var _val = 0;

                    switch(parseFloat(this.searchArtists.at(el.dataset["idx"]).get("distance"))){
                        case -1:
                            _val = 4;
                            break;
                        case 0.125:
                            _val = 3;
                            break;
                        case 0.25:
                            _val = 2;
                            break;
                        case 0.5:
                            _val = 1;
                            break;
                        case 0.7:
                            _val = 0;
                            break;
                        default:
                            _val = 1;
                            break;
                    }

                    if( _val < 1){
                        _val = 1;
                    }

                    $(el).noUiSlider({
                        start: [_val],
                        step: 1,
                        range: {
                            'min': 1,
                            'max': 4
                        }
                    });
                }.bind(this));

                this.$searchArtists.find(".edgeSpinner").Link('lower').to(function(value, e){
                    value = parseInt(value);

                    var str = "";
                    var _val = 0;

                    var idx = e.parent().parent().parent()[0].dataset["idx"];

                    switch (value){
                        case 0:
                            str = "Related";
                            _val = 0.7;
                            break;
                        case 1:
                            str = "Related";
                            _val = 0.5;
                            break;
                        case 2:
                            str = "Similar";
                            _val = 0.25;
                            break;
                        case 3:
                            str = "Very Similar";
                            _val = 0.125;
                            break;
                        case 4:
                            str = "None";
                            _val = -1;
                            break;
                    }

                    e.parent().parent().parent().next().html(str);
                    this.searchArtists.at(idx).set("distance", _val);
                }.bind(this));

                this.$searchArtists.find(".mdt-artist-name").click(function(e){
                    EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["id"]);
                });
            }
            else{
                this.$searchArtists.append(this.artistTemplateEmpty({col_span: 3,message:"No artists included!"}));
            }
        },

        importSettings: function(uuid){
            Util.showLoadingIndicator();

            $.ajax({
                url: Constants.baseUrl + "?a=getdetectivedetails&uuid=" + uuid,
                xhrFields:{'withCredentials': true}
            }).done(function(data){
                try{
                    var obj = JSON.parse(data);

                    this.searchArtists.reset();

                    obj["artists"].forEach(function(el){
                        this.searchArtists.add(el);
                    }.bind(this));

                    this.renderArtists();
                    this.applyAdvancedSettings(obj);
                    Util.hideLoadingIndicator();
                }
                catch(e){
                    Util.hideLoadingIndicator();
                    Util.alert("Error Parsing Json in Import Settings! (" + e + ")");
                }
            }.bind(this));
        },

        importExcludedArtists: function(uuid){
            Util.showLoadingIndicator();

            $.ajax({
                url: Constants.baseUrl + "?a=getdetectiveexcludes&uuid=" + uuid + "&artists=1",
                xhrFields:{'withCredentials': true}
            }).done(function(data){
                try{
                    var obj = JSON.parse(data);

                    obj.forEach(function(el){
                        this.excludeArtists.add(el);
                    }.bind(this));

                    this.renderExcludedArtists();
                    Util.hideLoadingIndicator();
                }
                catch(e){
                    Util.hideLoadingIndicator();
                    Util.alert("Error Parsing Json in Import Excluded Artists! (" + e + ")");
                }
            }.bind(this));
        },

        importExcludedSongs: function(uuid){
            Util.showLoadingIndicator();

            $.ajax({
                url: Constants.baseUrl + "?a=getdetectiveexcludes&uuid=" + uuid + "&songs=1",
                xhrFields:{'withCredentials': true}
            }).done(function(data){
                try{
                    var obj = JSON.parse(data);

                    obj.forEach(function(el){
                        this.excludeSongs.add(el);
                    }.bind(this));

                    this.renderExcludedSongs();
                    Util.hideLoadingIndicator();
                }
                catch(e){
                    Util.hideLoadingIndicator();
                    Util.alert("Error Parsing Json in Import Excluded Songs! (" + e + ")");
                }
            }.bind(this));
        },

        showCurrentStep: function(){
            this.$.find(".mdt-step-view").hide();
            this.$.find(".mdt-step-" + (this.currentStep + 1)).show();

            this.$.find(".hvr-underline-from-center").removeClass("mdt-selected");
            this.$.find(".mdt-bar-step-" + (this.currentStep + 1)).addClass("mdt-selected");

            if( this.currentStep == 2 ){
                if( !this.excludeArtistsLoaded && this.editMode ){
                    Util.showLoadingIndicator();
                    Util.showLoadingSlider();
                    this.excludeArtists.fetch({xhrFields:{'withCredentials': true}, url: Constants.baseUrl + "?a=getdetectiveexcludes&uuid=" + this.editMode + "&artists=1", reset: true});
                }
                else if ( !this.excludeArtistsLoaded ){
                    this.renderExcludedArtists();
                }

                this.excludeArtistsLoaded = true;
            }
            else if( this.currentStep == 3 ){
                if( !this.excludeSongsLoaded && this.editMode ){
                    Util.showLoadingIndicator();
                    Util.showLoadingSlider();
                    this.excludeSongs.fetch({xhrFields:{'withCredentials': true}, url: Constants.baseUrl + "?a=getdetectiveexcludes&uuid=" + this.editMode + "&songs=1", reset: true});
                }
                else if( !this.excludeSongsLoaded ){
                    this.renderExcludedSongs();
                }

                this.excludeSongsLoaded = true;
            }
        },

        render: function(uuid){
            require(["jquery.nouislider.all.min"], function(){
                if( !this.rendered ){
                    this.template = Handlebars.compile($("#" + this.templateId).html())();
                    this.artistTemplate = Handlebars.compile($("#mdtArtistTemplate").html());
                    this.artistTemplateEmpty = Handlebars.compile($("#mdtArtistTemplateEmpty").html());
                    this.exSongTemplate = Handlebars.compile($("#mdtExcludeSongTemplate").html());
                    this.exArtistTemplate = Handlebars.compile($("#mdtExcludeArtistTemplate").html());
                    this.importPopupTemplate = Handlebars.compile($("#modifyDetectivesImportTemplate").html());
                    this.exportPopupTemplate = Handlebars.compile($("#modifyDetectivesExportPopupTemplate").html());

                    this.$.append(this.template);

                    this.$searchArtists = this.$.find(".mdt-search-artists");
                    this.$excludeSongs = this.$.find(".mdt-exclude-songs");
                    this.$excludeArtists = this.$.find(".mdt-exclude-artists");
                    this.$searchArtistsInput = this.$.find("#mdt-search-artist");

                    this.$songPopSlider = this.$.find("#mdt-song-popularity");
                    this.$artistPopSlider = this.$.find("#mdt-artist-popularity");
                    this.$releaseDateSlider = this.$.find("#mdt-release-date");

                    this.$excludeRemixChkbx = this.$.find("#mdt-exclude-remix");
                    this.$excludeAcousticChkbx = this.$.find("#mdt-exclude-acoustic");
                    this.$excludeCollectionChkbx = this.$.find("#mdt-exclude-collection");

                    this.$deleteDetective = this.$.find(".mdt-delete-detective");

                    this.$artistTotal = this.$.find(".mdt-artist-total");

                    this.popularArtists.fetch();

                    //if( !logged_in ){
                    //    this.$.find(".mdt-btn-wrapper-step-1").hide();
                    //}

                    this.assignHandlers();
                    this.rendered = true;
                }

                this.$artistTotal.html("?");

                if( uuid && uuid.uuid ){
                    this.lastView = uuid.lastView;
                    uuid = uuid.uuid;
                }
                else{
                    this.lastView = Constants.showDetectivesView;
                }

                this.$deleteDetective.hide();

                // Reset Edit Models
                this.searchArtists.reset();

                this.excludeArtists.reset();
                this.renderExcludedArtists();

                this.excludeSongs.reset();
                this.renderExcludedSongs();

                if( uuid ){
                    EventPipe.trigger("router.navigate", Constants.routes.modifyDetective + uuid);
                    this.editMode = uuid;

                    if( !logged_in && !localStorage ){
                        Util.alert("Please get a newer browser with localStorage support to edit existing Detectives!");
                        EventPipe.trigger(Constants.contentView + "-showDetectivesView");
                        return;
                    }

                    // Check if temp detective
                    if( !logged_in && localStorage ){
                        var tempDetectives = (localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];
                        var idx = tempDetectives.map(function(el){
                            return el.uuid;
                        }).indexOf(uuid);

                        if( idx >= 0 ){
                            this.editModel.clear();

                            // Transform it back
                            this.editModel.set({
                                "uuid": tempDetectives[idx].uuid,
                                "artists": tempDetectives[idx].artist_model,
                                "name":tempDetectives[idx].name,
                                "owner_id":"-1337",
                                "min_song_pop":(tempDetectives[idx].hasOwnProperty("mnsp")) ? tempDetectives[idx].mnsp : null,
                                "max_song_pop":(tempDetectives[idx].hasOwnProperty("mxsp")) ? tempDetectives[idx].mxsp : null,
                                "min_artist_pop":(tempDetectives[idx].hasOwnProperty("mnap")) ? tempDetectives[idx].mnap : null,
                                "max_artist_pop":(tempDetectives[idx].hasOwnProperty("mxap")) ? tempDetectives[idx].mxap : null,
                                "min_release_date":(tempDetectives[idx].hasOwnProperty("mnrd")) ? tempDetectives[idx].mnrd : null,
                                "max_release_date":(tempDetectives[idx].hasOwnProperty("mxrd")) ? tempDetectives[idx].mxrd : null,
                                "exclude_remix":(tempDetectives[idx].hasOwnProperty("exr")) ? tempDetectives[idx].exr : null,
                                "exclude_acoustic":(tempDetectives[idx].hasOwnProperty("exa")) ? tempDetectives[idx].exa : null,
                                "exclude_collection":null
                            });

                            this.editDetective();
                        }
                        else{
                            //Util.showLoadingIndicator();
                            Util.showLoadingSlider();
                            this.editModel.fetch({xhrFields:{'withCredentials': true}, url: Constants.baseUrl + "?a=getdetectivedetails&uuid=" + uuid});
                        }

                        this.$.find(".mdt-export-btn,.mdt-import-settings").hide();
                    }
                    else if( logged_in ){
                        //Util.showLoadingIndicator();
                        Util.showLoadingSlider();
                        this.editModel.fetch({xhrFields:{'withCredentials': true}, url: Constants.baseUrl + "?a=getdetectivedetails&uuid=" + uuid});
                        this.$.find(".mdt-export-btn").show();
                    }

                    this.$.find(".mdt-headline").html("Edit Detective");
                }
                else{
                    EventPipe.trigger("router.navigate", Constants.routes.modifyDetective + "0");
                    this.editMode = null;
                    this.$.find(".detectiveName").val("");

                    this.$.find(".mdt-headline").html("Create New Detective");
                    this.renderArtists();
                    this.resetAdvancedSettings();

                    this.$.find(".mdt-export-btn").hide();
                }

                this.currentStep = 0;
                this.excludeSongsLoaded = false;
                this.excludeArtistsLoaded = false;

                this.showCurrentStep();
                this.$.show();
            }.bind(this));
        },

        hide: function(){
            if( this.rendered ){
                this.$excludeSongs.html("");
                this.$excludeArtists.html("");
                this.$searchArtists.html("");
            }

            this.$.hide();
        }
    });

    /**
     * Home / Show Detectives View
     * @type {void|*}
     */
    var ShowDetectivesView = Backbone.View.extend({
        templateId: "ShowDetectivesTemplate",
        sid: Constants.showDetectivesView,

        router: null,
        currentType: 1,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;
            this.registerEvents();
        },

        assignHandlers: function(){
            require(["chosen.jquery.min"], function(){
                this.$.find(".selectField").chosen({disable_search_threshold: 99, width: "200px"}).change(function(e){
                    this.currentType = $(e.currentTarget).val();
                    this.showPublicDetectives();
                }.bind(this));
            }.bind(this));
        },

        registerEvents: function(){
            GlobalCollections.detectives.on("sync", function(){
                this.updateDetectives();

                if( !$(".container-fluid").is(":visible") ){
                    window.setTimeout(function(){
                        $(".container-fluid").show();
                        $("#LoadingScreen").hide();

                        var b = (function get_browser_info(){
                            var ua=navigator.userAgent,tem,M=ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
                            if(/trident/i.test(M[1])){
                                tem=/\brv[ :]+(\d+)/g.exec(ua) || [];
                                return {name:'ie',version:(tem[1]||'')};
                            }
                            if(M[1]==='Chrome'){
                                tem=ua.match(/\bOPR\/(\d+)/)
                                if(tem!=null)   {return {name:'Opera', version:tem[1]};}
                            }
                            M=M[2]? [M[1], M[2]]: [navigator.appName, navigator.appVersion, '-?'];
                            if((tem=ua.match(/version\/(\d+)/i))!=null) {M.splice(1,1,tem[1]);}
                            return {
                                name: M[0].toLowerCase(),
                                version: M[1]
                            };
                        })();

                        if( (b.name == 'ie' && parseInt(b.version) <= 10) ||
                            (b.name == 'msie' && parseInt(b.version) <= 10) ||
                            (b.name == 'opera' && parseInt(b.version) < 30) ||
                            (b.name == 'safari' && parseInt(b.version) <= 8) ||
                            (b.name == 'firefox' && parseInt(b.version) < 34) ||
                            (b.name == 'chrome' && parseInt(b.version) < 43)){
                            Util.alert("Unsupported browser, please use a newer browser!");
                        }
                    }, 1500);
                }

                // Start
                this.router = new SoundDetectiveRouter();
                window._router = this.router;

                // Start Backbone history a necessary step for bookmarkable URL's
                Backbone.history.start();
            }.bind(this));

            EventPipe.on(Constants.showDetectivesView + "-updateDetectives", function(){
                this.updateDetectives();
            }.bind(this));
        },

        updateDetectives: function(){
            // Not logged in
            if( !logged_in && localStorage ){
                var tempDet = JSON.parse(localStorage.getItem("tempDetectives"))

                if(tempDet){
                    tempDet.forEach(function(el){
                        var idx = GlobalCollections.detectives.toJSON().map(function(el){
                            return el.uuid;
                        }).indexOf(el.uuid);

                        if( idx >= 0 ){
                            GlobalCollections.detectives.remove(GlobalCollections.detectives.at(idx));
                        }

                        GlobalCollections.detectives.add({
                            image: Constants.imageUrl,
                            name: el.name,
                            owner_id: "-1337",
                            uuid: el.uuid
                        });
                    }.bind(this));
                }
            }

            this.showDetectives();
        },

        showPublicDetectives: function(delay){
            this.$publicDetectives.html("");

            if( !delay ){
                delay = 0;
            }

            GlobalCollections.detectives.toJSON().forEach(function(el, idx){
                el.idx = idx;

                if( !parseInt(el.owner_id) ){
                    var public_type = el.public_type.split(",");

                    if( public_type.indexOf(this.currentType.toString()) > -1 ){
                        el.show = "hide";
                        el.delay = (Math.min(delay,5) * 0.2) + "s";
                        this.$publicDetectives.append(this.detectiveTemplate(el));
                        delay++;
                    }
                }
            }.bind(this));

            this.addEventsToDetectives(this.$publicDetectives);
        },

        addEventsToDetectives: function(ele){
            ele.find(".detective").click(function(e){
                // Check if temp detective
                if( parseInt(GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].owner_id) === -1337 ){
                    var tempDetectives = (localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];
                    var idx = tempDetectives.map(function(el){
                        return el.uuid;
                    }).indexOf(GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].uuid);

                    EventPipe.trigger(Constants.contentView + "-showExploreView");
                    EventPipe.trigger(Constants.exploreView + "-exploreDetective", {uuid: tempDetectives[idx].uuid, temp: tempDetectives[idx], name: tempDetectives[idx].name, image: (this.editMode) ? tempDetectives[idx].image : Constants.imageUrl}, true);
                }
                else{
                    EventPipe.trigger(Constants.contentView + "-showExploreView");
                    EventPipe.trigger(Constants.exploreView + "-exploreDetective", GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]]);
                }
            }.bind(this));

            ele.find(".detective-edit").click(function(e){
                EventPipe.trigger(Constants.contentView + "-editDetective", GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].uuid);
            }.bind(this));

            ele.find(".detective-delete").click(function(e){
                // Check if temp detective
                if( parseInt(GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].owner_id) === -1337 ){
                    var tempDetectives = (localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];
                    var idx = tempDetectives.map(function(el){
                        return el.uuid;
                    }).indexOf(GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].uuid);

                    tempDetectives.splice(idx, 1);

                    localStorage.setItem("tempDetectives", JSON.stringify(tempDetectives));
                    GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    var name = GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].name;

                    $.ajax({
                        xhrFields:{'withCredentials': true},
                        url: Constants.baseUrl + "?a=deletedetective&uuid=" + GlobalCollections.detectives.toJSON()[e.currentTarget.dataset["id"]].uuid
                    }).done(function(data){
                        GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});
                    });

                    Util.alert("Detective '" + name + "' deleted!");
                }
            }.bind(this));

            ele.find(".newDetective").click(function(e){
                if(typeof(Storage) !== "undefined" || logged_in) {
                    // Code for localStorage/sessionStorage.
                    EventPipe.trigger(Constants.contentView + "-createDetective");
                }
                else{
                    Util.alert("Your browser doesn't support local storage! Please use a newer browser to create your personal detectives!");
                }
            }.bind(this));
        },

        showDetectives: function(){
            this.$myDetectives.find("> div:not(:first-child)").remove();

            var publicCnt = 0;

            var yourDetectives = [];

            GlobalCollections.detectives.toJSON().forEach(function(el, idx){
                el.idx = idx;

                if( parseInt(el.owner_id) ){
                    yourDetectives.push(el);
                }
            }.bind(this));

            yourDetectives.forEach(function(el){
                el.show = "show";
                el.delay = (Math.min(publicCnt,5) * 0.2) + "s";
                this.$myDetectives.append(this.detectiveTemplate(el));
                publicCnt++;
            }.bind(this));

            this.addEventsToDetectives(this.$myDetectives);

            this.showPublicDetectives(publicCnt);
            this.markCurrentDetective();
        },

        markCurrentDetective: function(){
            this.$.find(".detective-wrapper > .detective").removeClass("currentDetective");

            if( this.parent.playerView && this.parent.playerView.detectiveUUID ){
                this.$.find(".detective-wrapper[data-uuid=\"" + this.parent.playerView.detectiveUUID + "\"] > .detective").addClass("currentDetective");
            }
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();
                this.detectiveTemplate = Handlebars.compile($("#DetectiveTemplate").html());

                this.$.append(this.template);
                this.$myDetectives = this.$.find(".myDetectives");
                this.$publicDetectives = this.$.find(".publicDetectives");

                this.assignHandlers();

                this.rendered = true;
                GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});
            }

            EventPipe.trigger("router.navigate", Constants.routes.showDetectives);

            this.markCurrentDetective();
            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Our Player which handles the playback of the songs either via youtube or spotify
     * @type {void|*}
     */
    var PlayerView = Backbone.View.extend({
        templateId: "PlayerTemplate",
        sid: Constants.playerView,

        /*
            Youtube Variables
         */
        ytPlayer: null,
        ytBusy: false,

        scPlayer: null,
        activePlayer: null,

        songQueue: new (Backbone.Collection.extend({
            model: Song
        }))(),

        currentSong: null,
        currentlyPlaying: null,

        volume: 100,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;

            this.registerEvents();
        },

        assignHandlers: function(){
            this.$.find(".playerWrongVideo > span").click(function(){
                if( this.currentSong && this.currentSong.id ){
                    $.ajax({
                        url: Constants.baseUrl + "?a=reportvideo&id=" + this.currentSong.id,
                        xhrFields:{'withCredentials': true}
                    });

                    Util.alert("Wrong Video reported! Thank you!");
                    this.playNextSong();
                }
            }.bind(this));

            this.$.find(".ytForward").click(this.playNextSong.bind(this));
            this.$.find(".ytBackward").click(this.playPreviousSong.bind(this));
            this.$.find(".ytPlay").click(function(){
                if( this.ytPlaying ){
                    this.stopYtSong();
                }
                else{
                    this.resumeYtSong();
                }
            }.bind(this));

            this.$.find(".removeSong").click(function(){
                $.ajax({
                    url: Constants.baseUrl + "?a=excludefromdetective&uuid=" + this.detectiveUUID + "&song_id=" + this.currentSong.id,
                    xhrFields:{'withCredentials': true}
                });

                EventPipe.trigger(Constants.exploreView + "-exSongFromList", {db_id: this.currentSong.id, uuid: this.detectiveUUID});

                var excludeId = this.currentSong.id;

                this.playNextSong();
                this.songQueue.remove(this.songQueue.findWhere({db_id: excludeId.toString()}));
                this.updateCurrentSongIndex();
            }.bind(this)).qtip({
                content: {
                    text: "Exclude Song From Detective"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$.find(".loveSong").click(function(){
                if( logged_in ){
                    if( this.currentSong && this.parent.collectionView.inCollection(this.currentSong.id) ){
                        EventPipe.trigger(Constants.collectionView + "-removeFromCollection", this.currentSong.id);
                        this.setLoveIcon();
                    }
                    else{
                        var currentSong = this.songQueue.toJSON()[this.currentSong.index];

                        // Because we saved it in the backend in seconds and js gives us ms
                        currentSong.is_in_collection = parseInt(new Date().getTime() / 1000);

                        EventPipe.trigger(Constants.collectionView + "-addToCollection", currentSong);
                        this.setLoveIcon();
                    }
                }
                else{
                    Util.alert("Please login to save songs!");
                }

            }.bind(this)).qtip({
                content: {
                    text: "Add To Collection"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$.find(".showSongQueue").click(function(){
                EventPipe.trigger(Constants.contentView + "-toggleCurrentlyPlayingView");

                if( this.currentSong.index + 1 >= this.songQueue.length ){
                    EventPipe.trigger(Constants.searchView + "-renderSongs", []);
                }
                else{
                    EventPipe.trigger(Constants.searchView + "-renderSongs", this.songQueue.toJSON().slice(this.currentSong.index + 1));
                }
            }.bind(this)).qtip({
                content: {
                    text: "Display Song Queue"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$.find(".spotifyLink").qtip({
                content: {
                    text: "Play On Spotify"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            require(["jquery.nouislider.all.min"], function() {
                this.$volumeControl.noUiSlider({
                    start: [100],
                    range: {
                        'min': 0,
                        'max': 100
                    }
                });

                this.$volumeControl.Link('lower').to(function(value){
                    this.volume = value;
                    this.setVolume();
                }.bind(this));

                this.$.find(".volumeControl").hover(function(){
                    if( this.ytPlayer ){
                        this.volume = this.ytPlayer.getVolume();
                        this.$volumeControl.val(this.volume);
                    }
                }.bind(this));
            }.bind(this));

            this.$currentArtist1.click(function(){
                var currentSong = this.songQueue.toJSON()[this.currentSong.index];
                EventPipe.trigger(Constants.contentView + "-openArtistView", currentSong.db_artist_id);
            }.bind(this));

            this.$currentArtist2.click(function(){
                var currentSong = this.songQueue.toJSON()[this.currentSong.index];
                EventPipe.trigger(Constants.contentView + "-openArtistView", currentSong.db_artist_id2);
            }.bind(this));

            this.$currentArtist3.click(function(){
                var currentSong = this.songQueue.toJSON()[this.currentSong.index];
                EventPipe.trigger(Constants.contentView + "-openArtistView", currentSong.db_artist_id3);
            }.bind(this));
        },

        syncPlayButtons: function(){
            if( this.currentSong && this.currentSong.id ){
                EventPipe.trigger(Constants.playerView + "-songPlaying", this.currentSong.id, this.currentlyPlaying);
            }
        },

        registerEvents: function(){
            EventPipe.on(Constants.playerView + "-removeSongFromQueue", function(ids){
                ids.forEach(function(el){
                    if( el == this.currentSong.id ){
                        this.playNextSong();
                    }

                    this.songQueue.remove(this.songQueue.findWhere({db_id: el.toString()}));
                    this.updateCurrentSongIndex();
                }.bind(this));
            }.bind(this));

            EventPipe.on(Constants.playerView + "-removeFromSongQueueDetective", function(obj){
                if( this.detectiveUUID == obj.uuid ){
                    obj.ids.forEach(function(el){
                        if( el == this.currentSong.id ){
                            this.playNextSong();
                        }

                        this.songQueue.remove(this.songQueue.findWhere({db_id: el.toString()}));
                        this.updateCurrentSongIndex();
                    }.bind(this));
                }
            }.bind(this));

            EventPipe.on(Constants.playerView + "-changeSongQueue", function(songs, detectiveUUID){
                this.detectiveUUID = detectiveUUID;

                //this.currentSong = null;
                this.songQueue.reset();
                this.songQueue.add(songs);
            }.bind(this));

            EventPipe.on(Constants.playerView + "-addSongQueueDetective", function(songs, detectiveUUID, song_id){
                if( this.detectiveUUID == detectiveUUID ){
                    this.songQueue.add(songs);

                    if( song_id && this.currentSong.id == song_id ){
                        this.playSongFromSDId(this.songQueue.toJSON()[this.currentSong.index].db_id);
                    }
                    else if( this.currentSong ){
                        EventPipe.trigger(Constants.searchView + "-renderSongs", (this.currentSong.index + 1 >= this.songQueue.length) ? [] : this.songQueue.toJSON().slice(this.currentSong.index + 1));
                    }
                }
            }.bind(this));

            EventPipe.on(Constants.playerView + "-skipSongs", function(songAmount){
                if( this.currentSong.index + songAmount > this.songQueue.length ){
                    return;
                }

                this.playSongFromSDId(this.songQueue.at(this.currentSong.index + songAmount).get("db_id"));
            }.bind(this));

            EventPipe.on(Constants.playerView + "-triggerCurrentSongEvents", this.syncPlayButtons.bind(this));

            EventPipe.on(Constants.playerView + "-setLoveIcon", this.setLoveIcon.bind(this));

            EventPipe.on(Constants.playerView + "-playNextSong", this.playNextSong.bind(this));

            EventPipe.on(Constants.playerView + "-playYtSong", this.playSongFromSDId.bind(this));

            EventPipe.on(Constants.playerView + "-playSpotifyTrackset", this.playSpotifyTrackset.bind(this));

            EventPipe.on(Constants.playerView + "-resumeSong", this.resumeYtSong.bind(this));

            EventPipe.on(Constants.playerView + "-stopSong", this.stopYtSong.bind(this));
        },

        setLoveIcon: function(){
            if( this.currentSong && this.parent.collectionView.inCollection(this.currentSong.id) ){
                this.$.find(".loveSong").addClass("songLoved");
            }
            else{
                this.$.find(".loveSong").removeClass("songLoved");
            }
        },

        setVolume: function(){
            if( this.ytPlayer ){
                this.ytPlayer.setVolume(this.volume);
            }

            if( this.scPlayer ){
                this.scPlayer.setVolume(this.volume/100);
            }
        },

        isSongQueuePlayable: function(){
            var isPlayable = false;

            this.songQueue.toJSON().forEach(function(el){
                if( GlobalCollections.ytSongCache[el.db_id] === undefined || GlobalCollections.ytSongCache[el.db_id] != false ){
                    isPlayable = true;
                }
            });

            return isPlayable;
        },

        updateCurrentSongIndex: function(){
            for(var i=0;i<this.songQueue.length;i++){
                if( this.songQueue.at(i).get("db_id") == this.currentSong.id ){
                    this.currentSong.index = i;
                }
            }
        },

        playSongFromSDId: function(id, playPrevious){
            if( !this.rendered ){
                this.render();
            }

            this.$loadingIndicator.show();
            this.$currentTitle.hide();
            this.$currentArtist.hide();

            if(this.detectiveUUID){
                this.$.find(".removeSong").show();
            }
            else{
                this.$.find(".removeSong").hide();
            }

            this.currentSong = {id: id, index: -1};

            if(typeof(window.localStorage) !== "undefined") {
                window.localStorage.setItem("s_" + id, new Date().getTime());
            }

            // Update Play Buttons
            this.syncPlayButtons();

            /**
             * Find out the song index in the songQueue
             */
            for(var i=0;i<this.songQueue.length;i++){
                if( this.songQueue.at(i).get("db_id") == this.currentSong.id ){
                    this.currentSong.index = i;

                    // Set Spotify Link
                    this.$spotifyLink.attr("href", "spotify:track:" + this.songQueue.at(i).get("available_spotify_id"));
                }
            }

            // Oops??
            if( this.currentSong.index == -1 ){
                return;
            }

            // Update Song List
            EventPipe.trigger(Constants.searchView + "-renderSongs", (this.currentSong.index + 1 >= this.songQueue.length) ? [] : this.songQueue.toJSON().slice(this.currentSong.index + 1));

            if( GlobalCollections.ytSongCache[this.currentSong.id] !== undefined ){
                if( GlobalCollections.ytSongCache[this.currentSong.id] != false ){
                    var data = GlobalCollections.ytSongCache[this.currentSong.id];

                    this.$currentTitle.show();
                    this.$currentArtist.show();
                    this.$loadingIndicator.hide();

                    if( data.indexOf("soundcloud:") != -1 ){
                        this.playScSong(data.split(":")[1]);
                    }
                    else{
                        this.playYtSong(data);
                    }
                }
                else{
                    // Avoid endless loop
                    if( !this.isSongQueuePlayable() ){
                        EventPipe.trigger(Constants.playerView + "-songPlaying", -1);
                        this.$currentTitle.show();
                        this.$currentArtist.show();
                        this.$loadingIndicator.hide();
                        return;
                    }

                    if( playPrevious ){
                        this.playPreviousSong();
                    }
                    else{
                        this.playNextSong();
                    }
                }
            }
            else{
                this.ytBusy = true;

                $.ajax({
                    xhrFields:{'withCredentials': true},
                    url: Constants.baseUrl + "?a=getvideoid&id=" + this.currentSong.id
                }).done(function(data){
                    if( !data ){
                        GlobalCollections.ytSongCache[this.currentSong.id] = false;
                        EventPipe.trigger(Constants.playerView + "-songNotAvailable", this.currentSong.id);

                        this.songQueue.at(this.currentSong.index).set({hide: true});

                        if( playPrevious ){
                            this.playPreviousSong();
                        }
                        else{
                            this.playNextSong();
                        }
                    }
                    else{
                        GlobalCollections.ytSongCache[this.currentSong.id] = data;

                        if( data.indexOf("soundcloud:") != -1 ){
                            this.playScSong(data.split(":")[1]);
                        }
                        else{
                            this.playYtSong(data);
                        }

                        this.$currentTitle.show();
                        this.$currentArtist.show();
                        this.$loadingIndicator.hide();
                    }

                    this.ytBusy = false;
                }.bind(this));
            }
        },

        playPreviousSong: function(){
            if( this.currentSong ){
                if (this.songQueue.length == 0){
                    return;
                }

                this.playSongFromSDId(((this.currentSong.index - 1)<0) ? this.songQueue.at(this.songQueue.length - 1).get("db_id") : this.songQueue.at(this.currentSong.index - 1).get("db_id"), true);
            }
        },

        playNextSong: function(){
            if( this.currentSong ){
                // Two Possibilities:
                // 1. We have a detective playing and it is not finished so we need to load more songs
                // 2. No detective playing or detective doesnt have more songs -> start from queue beginning
                if( (this.currentSong.index + 1)>=this.songQueue.length && this.detectiveUUID && this.parent.exploreView.activeDetective.uuid == this.detectiveUUID && !this.parent.exploreView.endOfDetective ){
                    EventPipe.trigger(Constants.exploreView + "-loadMoreAndPlay");
                }
                else if (this.songQueue.length == 0){
                    return;
                }
                else{
                    this.playSongFromSDId(((this.currentSong.index + 1)>=this.songQueue.length) ? this.songQueue.at(0).get("db_id") : this.songQueue.at(this.currentSong.index + 1).get("db_id"));
                }
            }
        },

        stopYtSong: function(){
            if( this.ytPlayer && this.activePlayer == this.ytPlayer ){
                this.ytPlayer.pauseVideo();
            }

            if( this.scPlayer && this.activePlayer == this.scPlayer ){
                this.scPlayer.pause();
            }
        },

        resumeYtSong: function(){
            if( this.ytPlayer && this.activePlayer == this.ytPlayer ){
                this.ytPlayer.playVideo();
                this.$ytPlayer.show();
                this.$spotifyPlayer.hide();
            }

            if( this.scPlayer && this.activePlayer == this.scPlayer ){
                this.scPlayer.play();
                this.$scPlayer.show();
                this.$spotifyPlayer.hide();
            }
        },

        playScSong: function(scId){
            this.$ytPlayerWrapper.hide();
            this.$spotifyPlayer.hide();

            this.$ytPlayer.show();
            this.$scPlayer.show();

            if( this.ytPlayer ){
                this.ytPlayer.pauseVideo();
            }

            if( !this.scPlayer ){
                require(["jquery.nouislider.all.min", "https://w.soundcloud.com/player/api.js"], function(){
                    this.$scPlayer.attr("src", "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/");

                    this.scPlayer = SC.Widget(this.$scPlayer[0]);

                    this.scPlayer.bind(SC.Widget.Events.READY, function(){
                        window.setTimeout(function(){
                            this.scPlayer.play();
                        }.bind(this), 3000);
                    }.bind(this));

                    this.scPlayer.load("https://api.soundcloud.com/tracks/" + scId, {"auto_play": "true"});
                    this.activePlayer = this.scPlayer;


                    this.scPlayer.bind(SC.Widget.Events.PLAY, function(){
                        this.ytPlaying = true;
                        this.$playBtn.addClass("ion-ios-pause")
                                     .removeClass("ion-ios-play");

                        EventPipe.trigger(Constants.playerView + "-songPlayingResume", this.currentSong.id);

                        this.currentlyPlaying = true;
                        this.setVolume();
                    }.bind(this));

                    this.scPlayer.bind(SC.Widget.Events.PAUSE, function(){
                        this.ytPlaying = false;
                        this.$playBtn.addClass("ion-ios-play")
                                     .removeClass("ion-ios-pause");

                        EventPipe.trigger(Constants.playerView + "-songPlayingPause", this.currentSong.id);

                        this.currentlyPlaying = false;
                    }.bind(this));

                    this.scPlayer.bind(SC.Widget.Events.FINISH, function(){
                        this.playNextSong();
                    }.bind(this));
                }.bind(this));
            }
            else{
                this.scPlayer.load("https://api.soundcloud.com/tracks/" + scId, {"auto_play": "true"});
                this.activePlayer = this.scPlayer;
            }

            var currentSong = this.songQueue.toJSON()[this.currentSong.index];

            this.$currentTitle.html(currentSong.title);
            this.$currentTitle.attr("title", currentSong.title);

            var splitted = currentSong.artist_name.split(";;");
            splitted.forEach(function(el, idx){
                if( idx == 0 ){
                    this.$currentArtist1.html(el);
                    this.$currentArtist1.attr("title", el);
                }
                else if( idx == 1 ){
                    this.$currentArtist2.html(el);
                    this.$currentArtist2.attr("title", el);
                }
                else{
                    this.$currentArtist3.html(el);
                    this.$currentArtist3.attr("title", el);
                }
            }.bind(this));

            if( splitted.length == 1 ){
                this.$currentArtist2.hide();
                this.$currentArtist3.hide();
            }
            else if( splitted.length == 2 ){
                this.$currentArtist2.show();
                this.$currentArtist3.hide();
            }
            else{
                this.$currentArtist2.show();
                this.$currentArtist3.show();
            }

            this.setLoveIcon();
        },

        playYtSong: function(ytId){
            this.$scPlayer.hide();
            this.$spotifyPlayer.hide();

            this.$ytPlayer.show();
            this.$ytPlayerWrapper.show();

            if( this.scPlayer ){
                this.scPlayer.pause();
            }

            if( !this.ytPlayer ){
                require(["jquery.nouislider.all.min", "https://www.youtube.com/iframe_api"], function(){
                    YT.ready(function(){
                        this.ytPlayer = new YT.Player('ytPlayer', {
                            height: '200',
                            width: '250',
                            videoId: ytId,
                            playerVars: {
                                'autoplay': 1,
                                'controls': 1,
                                'rel' : 0,
                                'iv_load_policy': 3
                            },
                            events: {
                                 'onReady': function(){
                                     this.volume = this.ytPlayer.getVolume();
                                     this.$volumeControl.val(this.volume);
                                     this.ytPlayer.playVideo();
                                 }.bind(this),
                                 'onStateChange': function(event){
                                     // video is paused inform our player listeners
                                     if( event.data == YT.PlayerState.PAUSED ){
                                         this.ytPlaying = false;
                                         this.$playBtn.addClass("ion-ios-play")
                                                      .removeClass("ion-ios-pause");

                                         EventPipe.trigger(Constants.playerView + "-songPlayingPause", this.currentSong.id);
                                         this.currentlyPlaying = false;
                                     }
                                     else if( event.data == YT.PlayerState.PLAYING ){
                                         this.ytPlaying = true;
                                         this.$playBtn.addClass("ion-ios-pause")
                                                      .removeClass("ion-ios-play");

                                         EventPipe.trigger(Constants.playerView + "-songPlayingResume", this.currentSong.id);
                                         this.currentlyPlaying = true;
                                     }
                                     else if( event.data == YT.PlayerState.ENDED ){
                                         this.playNextSong();
                                     }
                                 }.bind(this)
                            }
                        });

                        this.activePlayer = this.ytPlayer;
                    }.bind(this));
                }.bind(this));
            }
            else{
                this.ytPlayer.loadVideoById(ytId);
                this.activePlayer = this.ytPlayer;
            }

            var currentSong = this.songQueue.toJSON()[this.currentSong.index];

            this.$currentTitle.html(currentSong.title);
            this.$currentTitle.attr("title", currentSong.title);

            var splitted = currentSong.artist_name.split(";;");
            splitted.forEach(function(el, idx){
                if( idx == 0 ){
                    this.$currentArtist1.html(el);
                    this.$currentArtist1.attr("title", el);
                }
                else if( idx == 1 ){
                    this.$currentArtist2.html(el);
                    this.$currentArtist2.attr("title", el);
                }
                else{
                    this.$currentArtist3.html(el);
                    this.$currentArtist3.attr("title", el);
                }
            }.bind(this));

            if( splitted.length == 1 ){
                this.$currentArtist2.hide();
                this.$currentArtist3.hide();
            }
            else if( splitted.length == 2 ){
                this.$currentArtist2.show();
                this.$currentArtist3.hide();
            }
            else{
                this.$currentArtist2.show();
                this.$currentArtist3.show();
            }

            this.setLoveIcon();
        },

        playSpotifyTrackset: function(){
            if( !this.rendered ){
                this.render();
            }

            this.$ytPlayer.hide();
            this.$scPlayer.hide();

            if( this.scPlayer ){
                this.scPlayer.pause();
            }
            if( this.ytPlayer ){
                this.ytPlayer.pauseVideo();
            }

            this.$spotifyPlayer.show();

            var spotifyIds = this.songQueue.toJSON().map(function(el){return el.available_spotify_id;}).slice(0,100).join(",");
            this.$spotifyIframe.attr("src", "https://embed.spotify.com/?uri=spotify:trackset:Sound Detective:" + spotifyIds);
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();
                this.$.append(this.template);
                this.$ytPlayer = this.$.find("#ytWrapper");
                this.$ytPlayerWrapper = this.$.find("#ytPlayer-Wrapper");

                this.$scPlayer = this.$.find("#scPlayer");
                this.$spotifyPlayer = this.$.find("#spotifyPlayer");
                this.$spotifyIframe = this.$spotifyPlayer.find("#spotifyIframe");

                this.$loadingIndicator = this.$.find(".ytLoading");

                this.$playBtn = this.$.find(".ytPlay");
                
                this.$currentArtist = this.$.find(".ytArtist");
                this.$currentArtist1 = this.$.find(".ytArtist1");
                this.$currentArtist2 = this.$.find(".ytArtist2");
                this.$currentArtist3 = this.$.find(".ytArtist3");
                
                this.$currentTitle = this.$.find(".ytTitle");
                this.$spotifyLink = this.$.find(".spotifyLink");

                this.$volumeControl = this.$.find(".volumeSlider");

                this.$ytPlayer.hide();
                this.$spotifyPlayer.hide();

                this.rendered = true;
                this.assignHandlers();

                // Show Mobile Player
                $("#sidebar").show();
                $("#mobileHeader .player-btn").show();
                $("#sidebar").removeClass("mb-hide");
                $("#mobileHeader .player-btn").removeClass("slideDown");

                window.setTimeout(function(){
                    $("#playerView").removeClass("playerClosed");
                }.bind(this), 50);
            }

            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Charts View
     * @type {void|*}
     */
    var ChartsView = Backbone.View.extend({
        templateId: "ChartsTemplate",
        sid: Constants.chartsView,

        displaySongs: [
            // All
            new (Backbone.Collection.extend({
                url: Constants.baseUrl + "?a=charts",
                fetched: false
            }))(),
            // House
            new (Backbone.Collection.extend({
                url: Constants.baseUrl + "?a=charts&genre=1",
                fetched: false
            }))(),
            // Rock
            new (Backbone.Collection.extend({
                url: Constants.baseUrl + "?a=charts&genre=2",
                fetched: false
            }))(),
            // Pop
            new (Backbone.Collection.extend({
                url: Constants.baseUrl + "?a=charts&genre=3",
                fetched: false
            }))(),
            // Hip hop
            new (Backbone.Collection.extend({
                url: Constants.baseUrl + "?a=charts&genre=4",
                fetched: false
            }))()
        ],

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;

            this.registerEvents();
        },

        registerEvents: function(){
            this.displaySongs[0].on("sync", function(){
                this.displaySongs[0].fetched = true;
                this.renderSongs(0);
            }.bind(this));
            this.displaySongs[1].on("sync", function(){
                this.displaySongs[1].fetched = true;
                this.renderSongs(1);
            }.bind(this));
            this.displaySongs[2].on("sync", function(){
                this.displaySongs[2].fetched = true;
                this.renderSongs(2);
            }.bind(this));
            this.displaySongs[3].on("sync", function(){
                this.displaySongs[3].fetched = true;
                this.renderSongs(3);
            }.bind(this));
            this.displaySongs[4].on("sync", function(){
                this.displaySongs[4].fetched = true;
                this.renderSongs(4);
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songNotAvailable", function(id){
                this.$.find(".songDisplay > div[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkNA");

                this.$.find(".ytLinkNA").qtip("destroy");
                this.$.find(".ytLinkNA").qtip({
                    content: {
                        text: "No Playable Song Found"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlaying", function(id, currentlyPlaying){
                this.$.find(".songDisplay > div").removeClass("playing");
                this.$.find(".songDisplay .ytLinkPause").removeClass("ytLinkPause");

                this.$.find(".songDisplay > div[data-id='" + id + "']").addClass("playing");

                if( currentlyPlaying ){
                    this.$.find(".songDisplay > div[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkPause");
                }
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingPause", function(id){
                this.$.find(".playing .ytLinkPlay").removeClass("ytLinkPause");
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingResume", function(id){
                this.$.find(".playing .ytLinkPlay").addClass("ytLinkPause");
            }.bind(this));
        },

        assignHandler: function(){
            this.$.find(".charts-0").click(function(){
                this.$.find(".sk-circle").show();
                Util.showLoadingSlider();
                this.$.find(".songDisplay").html("");

                if( !this.displaySongs[0].fetched ){
                    this.displaySongs[0].fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    this.renderSongs(0);
                }

                this.$.find(".charts-bar > span").removeClass("mdt-selected");
                this.$.find(".charts-0").addClass("mdt-selected");
            }.bind(this));

            this.$.find(".charts-1").click(function(){
                this.$.find(".sk-circle").show();
                Util.showLoadingSlider();
                this.$.find(".songDisplay").html("");

                if( !this.displaySongs[1].fetched ){
                    this.displaySongs[1].fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    this.renderSongs(1);
                }

                this.$.find(".charts-bar > span").removeClass("mdt-selected");
                this.$.find(".charts-1").addClass("mdt-selected");
            }.bind(this));

            this.$.find(".charts-2").click(function(){
                this.$.find(".sk-circle").show();
                Util.showLoadingSlider();
                this.$.find(".songDisplay").html("");

                if( !this.displaySongs[2].fetched ){
                    this.displaySongs[2].fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    this.renderSongs(2);
                }

                this.$.find(".charts-bar > span").removeClass("mdt-selected");
                this.$.find(".charts-2").addClass("mdt-selected");
            }.bind(this));

            this.$.find(".charts-3").click(function(){
                this.$.find(".sk-circle").show();
                Util.showLoadingSlider();

                this.$.find(".songDisplay").html("");

                if( !this.displaySongs[3].fetched ){
                    this.displaySongs[3].fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    this.renderSongs(3);
                }

                this.$.find(".charts-bar > span").removeClass("mdt-selected");
                this.$.find(".charts-3").addClass("mdt-selected");
            }.bind(this));

            this.$.find(".charts-4").click(function(){
                this.$.find(".sk-circle").show();
                Util.showLoadingSlider();
                this.$.find(".songDisplay").html("");

                if( !this.displaySongs[4].fetched ){
                    this.displaySongs[4].fetch({xhrFields:{'withCredentials': true}});
                }
                else{
                    this.renderSongs(4);
                }

                this.$.find(".charts-bar > span").removeClass("mdt-selected");
                this.$.find(".charts-4").addClass("mdt-selected");
            }.bind(this));
        },

        renderSongs: function(idx){
            var t = "";

            this.displaySongs[idx].each(function(item){
                var el = item.toJSON();

                el.in_collection = (this.parent.collectionView.inCollection(el.db_id)) ? "songLoved" : "";
                el.isNA = (GlobalCollections.ytSongCache[el.db_id] !== undefined && GlobalCollections.ytSongCache[el.db_id] == false) ? "ytLinkNA" : "";

                el.artists = [];
                el.artist_name.split(";;").forEach(function(item, idx){
                    var artist_id = 0;

                    if( idx == 0 ){
                        artist_id = el.db_artist_id;
                    }
                    else if( idx == 1 ){
                        artist_id = el.db_artist_id2;
                    }
                    else if( idx == 2 ){
                        artist_id = el.db_artist_id3;
                    }

                    el.artists.push({
                        artist_name: item,
                        db_artist_id: artist_id
                    });
                });

                t += this.songTemplate(el);
            }.bind(this));

            this.$.find(".songDisplay").html(t);

            this.$.find(".ytLinkNA").qtip("destroy");
            this.$.find(".ytLinkNA").qtip({
                content: {
                    text: "No Playable Song Found"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".artistLink").click(function(e){
                EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["artist"]);
            });

            this.$.find(".songAddColl").click(function(e){
                if( logged_in ){
                    // is song in collection
                    var db_id = e.currentTarget.dataset["id"];

                    if( this.parent.collectionView.inCollection(db_id) ){
                        EventPipe.trigger(Constants.collectionView + "-removeFromCollection", db_id);
                    }
                    else{
                        var obj = null;

                        this.displaySongs[idx].toJSON().forEach(function(el){
                            if( el.db_id == db_id ){
                                obj = el;
                            }
                        })

                        // Because we saved it in the backend in seconds and js gives us ms
                        obj.is_in_collection = parseInt(new Date().getTime() / 1000);

                        EventPipe.trigger(Constants.collectionView + "-addToCollection", obj);
                    }
                }
                else{
                    Util.alert("Please login to save songs!");
                }
            }.bind(this)).qtip({
                content: {
                    text: "Add To Collection"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$.find(".spotifyLinkBlack").qtip({
                content: {
                    text: "Play On Spotify"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".ytLink").each(function(_idx, el){
                $(el).click(function(e){
                    if ( $(el).parent().parent().hasClass("playing") ){
                        if( $(el).hasClass("ytLinkPause") ){
                            EventPipe.trigger(Constants.playerView + "-stopSong");
                        }
                        else {
                            EventPipe.trigger(Constants.playerView + "-resumeSong");
                        }
                    }
                    else{
                        if ( !$(el).hasClass("ytLinkNA") ){
                            EventPipe.trigger(Constants.playerView + "-changeSongQueue", this.displaySongs[idx].toJSON());
                            EventPipe.trigger(Constants.playerView + "-playYtSong", e.currentTarget.dataset["id"]);
                        }
                    }
                }.bind(this));
            }.bind(this));

            this.$.find(".sk-circle").hide();
            Util.finishLoadingSlider();

            EventPipe.trigger(Constants.playerView + "-triggerCurrentSongEvents");
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();
                this.songTemplate = Handlebars.compile($("#ChartsViewSongTemplate").html());

                this.$.append(this.template);
                this.rendered = true;

                this.assignHandler();
                this.$.find(".charts-0").click();
            }

            EventPipe.trigger("router.navigate", Constants.routes.charts);
            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Explore View
     * @type {void|*}
     */
    var ExploreView = Backbone.View.extend({
        templateId: "ExploreTemplate",
        sid: Constants.exploreView,

        displaySongs: new (Backbone.Collection.extend({
            model: Song,
            url: Constants.baseUrl
        }))(),
        activeDetective: null,
        songTemplate: null,

        endOfDetective: false,
        songsPerRequest: 30,

        lastSongIdRendered: 0,

        tempDetective: false,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;

            this.registerEvents();
        },

        registerEvents: function(){
            EventPipe.on(Constants.exploreView + "-exSongFromList", function(obj){
                if( logged_in ){
                    if( obj.uuid == this.activeDetective.uuid && parseInt(this.activeDetective.owner_id) != 0 ){
                        if( this.displaySongs.findWhere({db_id: obj.db_id.toString()}) ){
                            this.displaySongs.remove(this.displaySongs.findWhere({db_id: obj.db_id.toString()}));

                            // Delete Song Div
                            this.$.find(".songDisplay > [data-id='" + obj.db_id + "'] .songExcludeBtn").qtip('hide');
                            this.$.find(".songDisplay > [data-id='" + obj.db_id + "']").remove();
                        }
                    }

                    GlobalCollections.detectives_detail.findWhere({uuid: obj.uuid}).excludeSong(obj.db_id.toString());
                }
                else{
                    Util.alert("Please login to exclude songs!");
                }
            }.bind(this));

            EventPipe.on(Constants.exploreView + "-exSong", function(db_id){
                this.excludeSong(db_id);
            }.bind(this));

            EventPipe.on(Constants.exploreView + "-exArtist", function(artist_id){
                this.excludeArtist(artist_id);
            }.bind(this));

            EventPipe.on(Constants.exploreView + "-exploreDetective", function(obj, forceRefresh, forceCompleteReload){
                Util.hideLoadingIndicator();

                // only update if new detective
                if( !this.activeDetective || this.activeDetective.uuid != obj.uuid || forceRefresh ){
                    this.activeDetective = obj;

                    this.$image.attr("src", obj.image);
                    this.$name.html(obj.name);

                    this.endOfDetective = false;
                    this.lastSongIndexRendered = 0;

                    this.tempDetective = false;

                    // add spinner
                    this.$.find(".exDetective > div").hide();
                    this.$.find(".sk-circle").show();
                    Util.showLoadingSlider();

                    this.displaySongs.reset();

                    // Is it a temporary detective??
                    if( obj.temp ){
                        this.tempDetective = true;
                        delete obj.temp.artist_model;
                    }

                    if( forceCompleteReload ){
                        var _model = GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid});

                        if( _model ){
                            GlobalCollections.detectives_detail.remove(_model);
                        }
                    }

                    if( GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid}) ){
                        var model = GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid});

                        if( model.get("refetch") === true ){
                            if( obj.temp){
                                model.set("tempParams", obj.temp);
                            }

                            model.fetch();
                        }
                        else{
                            model.onChangeFinished();
                        }
                    }
                    else{
                        var model = new Detective_Details();

                        model.set('uuid', this.activeDetective.uuid);

                        if( obj.temp ) {
                            model.set('tempDetective', true);
                            model.set('tempParams', obj.temp);
                        }

                        model.onChangeFinished = function(){
                            if( this.activeDetective.uuid === model.get("uuid") ){
                                if( !this.tempDetective ) {
                                    this.activeDetective = model.get("detective_info");
                                }

                                this.$.find(".songDisplay").html("");
                                this.renderDetectiveOverview();

                                this.$.find(".exDetective > div").show();
                                this.$.find(".sk-circle").show();

                                model.getNextSongs();
                            }
                        }.bind(this);

                        model.onLoadMoreFinished = function(songs, playNext){
                            this.$.find(".sk-circle").hide();
                            Util.finishLoadingSlider();

                            if( songs.length > 0 ){
                                songs.forEach(function(el){
                                    this.displaySongs.add(el);
                                }.bind(this));

                                this.renderSongs(true);

                                // Update Player
                                EventPipe.trigger(Constants.playerView + "-addSongQueueDetective", songs, this.activeDetective.uuid);

                                if( playNext ){
                                    EventPipe.trigger(Constants.playerView + "-playNextSong");
                                }
                            }
                            else{
                                this.endOfDetective = true;
                                this.$.find(".songDisplay").append('<div style="text-align: center;" class="song">No more songs to display!</div>');
                            }
                        }.bind(this);

                        model.fetch();

                        // Create New Model
                        GlobalCollections.detectives_detail.add(model);
                    }

                    this.$.scrollTop(0);
                }

                EventPipe.trigger("router.navigate", Constants.routes.applyDetective + this.activeDetective.uuid);
            }.bind(this));

            EventPipe.on(Constants.exploreView + "-loadMoreAndPlay", function(){
                this.loadMore(true);
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songNotAvailable", function(id){
                this.$.find(".songDisplay > div[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkNA");

                this.$.find(".ytLinkNA").qtip("destroy");
                this.$.find(".ytLinkNA").qtip({
                    content: {
                        text: "No Playable Song Found"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlaying", function(id){
                this.$.find(".songDisplay > div").removeClass("playing");
                this.$.find(".songDisplay .ytLinkPause").removeClass("ytLinkPause");

                this.$.find(".songDisplay > div[data-id='" + id + "']").addClass("playing");
                this.$.find(".songDisplay > div[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkPause");
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingPause", function(id){
                this.$.find(".playing .ytLinkPlay").removeClass("ytLinkPause");
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingResume", function(id){
                this.$.find(".playing .ytLinkPlay").addClass("ytLinkPause");
            }.bind(this));
        },

        renderDetectiveOverview: function(){
            this.$image.attr("src", this.activeDetective.image);
            this.$name.html(this.activeDetective.name);

            this.$.find(".exArtistsContainer").html("");

            if( this.activeDetective.owner_id !== undefined && parseInt(this.activeDetective.owner_id) >= 0 ){
                // Display Artists
                this.activeDetective.artists.forEach(function(el){
                    var similar_text = "";
                    
                    if( parseFloat(el.distance) <= 0.125 && parseFloat(el.distance) > 0 ){
                        similar_text = " & very similar artists";
                    }
                    else if( parseFloat(el.distance) <= 0.25 && parseFloat(el.distance) > 0 ){
                        similar_text = " & similar artists";
                    }
                    else if( parseFloat(el.distance) <= 0.5 && parseFloat(el.distance) > 0 ){
                        similar_text = " & related artists";
                    }

                    if( !el.image_small ){
                        el.image_small = Constants.smallImageNotFoundUrl;
                    }

                    this.$.find(".exArtistsContainer").append(this.exploreArtistTemplate({
                        image_small: el.image_small,
                        name: el.name,
                        id: el.id,
                        similar_text: similar_text
                    }));
                }.bind(this));
            }
            else{
                var tempDetectives = JSON.parse(localStorage.getItem("tempDetectives"));
                var idx = tempDetectives.map(function(el){
                    return el.uuid;
                }).indexOf(this.activeDetective.uuid);

                // Display Artists
                tempDetectives[idx].artist_model.forEach(function(el){
                    var similar_text = "";

                    if( parseFloat(el.distance) <= 0.25 && parseFloat(el.distance) > 0 ){
                        similar_text = " & very similar artists";
                    }
                    else if( parseFloat(el.distance) <= 0.5 && parseFloat(el.distance) > 0 ){
                        similar_text = " & similar artists";
                    }
                    else if( parseFloat(el.distance) <= 1 && parseFloat(el.distance) > 0 ){
                        similar_text = " & related artists";
                    }

                    if( !el.image_small ){
                        el.image_small = Constants.smallImageNotFoundUrl;
                    }

                    this.$.find(".exArtistsContainer").append(this.exploreArtistTemplate({
                        image_small: el.image_small,
                        name: el.name,
                        id: el.id,
                        similar_text: similar_text
                    }));
                }.bind(this));
            }

            this.$.find(".exploreArtistTile").each(function(idx, el) {
                $(el).qtip({
                    content: {
                        text: $(el).data().name + $(el).data().text
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true
                    }
                });

                $(el).click(function(e){
                    EventPipe.trigger(Constants.contentView + "-openArtistView",  $(el).data().id);
                });
            });

            if( this.activeDetective.owner_id !== undefined && parseInt(this.activeDetective.owner_id) >= 0 ){
                // Options exOptionsContainer
                var min_pop = (this.activeDetective.min_song_pop) ? this.activeDetective.min_song_pop : 0;
                var max_pop = (this.activeDetective.max_song_pop) ? this.activeDetective.max_song_pop : 100;

                this.$.find(".songPop").html(Util.getSongPopularityLongString(min_pop) + " - " + Util.getSongPopularityLongString(max_pop));

                min_pop = (this.activeDetective.min_artist_pop) ? this.activeDetective.min_artist_pop : 0;
                max_pop = (this.activeDetective.max_artist_pop) ? this.activeDetective.max_artist_pop : 100;

                this.$.find(".artistPop").html(Util.getArtistPopularityLongString(min_pop) + " - " + Util.getArtistPopularityLongString(max_pop));

                if( this.activeDetective && this.activeDetective.min_release_date < 0 ){
                    var min_rel_date = new Date(new Date().getTime() + this.activeDetective.min_release_date * 1000).toLocaleDateString();
                }
                else{
                    var min_rel_date = (this.activeDetective.min_release_date) ? new Date(this.activeDetective.min_release_date * 1000).toLocaleDateString() : new Date(0).toLocaleDateString();
                }

                if( this.activeDetective && this.activeDetective.min_release_date < 0 ){
                    var max_rel_date = new Date(new Date().getTime() + this.activeDetective.max_release_date * 1000).toLocaleDateString();
                }
                else{
                    var max_rel_date = (this.activeDetective.max_release_date) ? new Date(this.activeDetective.max_release_date * 1000).toLocaleDateString() : new Date().toLocaleDateString();
                }

                this.$.find(".releaseDate").html(" " + min_rel_date + " - " + max_rel_date);
            }
            else{
                // Options exOptionsContainer
                var min_pop = (this.activeDetective.temp.mnsp) ? this.activeDetective.temp.mnsp : 0;
                var max_pop = (this.activeDetective.temp.mxsp) ? this.activeDetective.temp.mxsp : 100;

                this.$.find(".songPop").html(Util.getSongPopularityLongString(min_pop) + " - " + Util.getSongPopularityLongString(max_pop));

                min_pop = (this.activeDetective.temp.mnap) ? this.activeDetective.temp.mnap : 0;
                max_pop = (this.activeDetective.temp.mxap) ? this.activeDetective.temp.mxap : 100;

                this.$.find(".artistPop").html(Util.getArtistPopularityLongString(min_pop) + " - " + Util.getArtistPopularityLongString(max_pop));

                var min_rel_date = (this.activeDetective.temp.mnrd) ? new Date(this.activeDetective.temp.mnrd * 1000).toLocaleDateString() : new Date(0).toLocaleDateString();
                var max_rel_date = (this.activeDetective.temp.mxrd) ? new Date(this.activeDetective.temp.mxrd * 1000).toLocaleDateString() : new Date().toLocaleDateString();

                this.$.find(".releaseDate").html(" " + min_rel_date + " - " + max_rel_date);
            }
        },

        renderSongs: function(append){
            if( this.rendered ){
                var t = "";

                this.displaySongs.each(function(obj){
                    if( !append ){
                        obj.set({rendered: false});
                    }

                    var item = obj.toJSON();

                    if( !item.rendered ){
                        item.in_collection = (this.parent.collectionView.inCollection(item.db_id)) ? "songLoved" : "";
                        item.isNA = (GlobalCollections.ytSongCache[item.db_id] !== undefined && GlobalCollections.ytSongCache[item.db_id] == false) ? "ytLinkNA" : "";

                        item.artists = [];
                        item.artist_name.split(";;").forEach(function(el, idx){
                            var artist_id = 0;

                            if( idx == 0 ){
                                artist_id = item.db_artist_id;
                            }
                            else if( idx == 1 ){
                                artist_id = item.db_artist_id2;
                            }
                            else if( idx == 2 ){
                                artist_id = item.db_artist_id3;
                            }

                            item.artists.push({
                                artist_name: el,
                                db_artist_id: artist_id
                            });
                        });

                        item.releaseDateString = new Date(parseInt(item.release_date)*1000).toLocaleDateString();

                        t += this.songTemplate(item);
                        obj.set({rendered: true});
                    }
                }.bind(this));

                if( !append ){
                    this.$.find(".songDisplay").html(t);
                }
                else{
                    this.$.find(".songDisplay").append(t);
                }

                // Events
                this.$.find(".artistLink").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["artist"]);
                        });
                    }
                }.bind(this));

                this.$.find(".ytLink").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            if ( $(el).parent().parent().hasClass("playing") ){
                                if( $(el).hasClass("ytLinkPause") ){
                                    EventPipe.trigger(Constants.playerView + "-stopSong");
                                }
                                else {
                                    EventPipe.trigger(Constants.playerView + "-resumeSong");
                                }
                            }
                            else{
                                if ( !$(el).hasClass("ytLinkNA") ){
                                    EventPipe.trigger(Constants.playerView + "-changeSongQueue", this.displaySongs.toJSON(), this.activeDetective.uuid);
                                    EventPipe.trigger(Constants.playerView + "-playYtSong", e.currentTarget.dataset["id"]);
                                }
                            }
                        }.bind(this));
                    }
                }.bind(this));

                this.$.find(".songExcludeBtn").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            this.excludeSong(e.currentTarget.dataset["id"]);
                        }.bind(this));

                        $(el).qtip({
                            content: {
                                text: this.exHoverTemplate({
                                    db_id: el.dataset["id"],
                                    artist_id: $(el.parentNode).find(".artistLink").data().artist,
                                    event_name_exsong: Constants.exploreView + "-exSong",
                                    event_name_exartist: Constants.exploreView + "-exArtist"
                                })
                            },
                            position: {
                                my: "bottom center",
                                at: "top center"
                            },
                            style: {
                                classes: "qtip-tipsy"
                            },
                            hide: {
                                fixed: true,
                                delay: 100
                            }
                        });
                    }
                }.bind(this));

                this.$.find(".spotifyLinkBlack").qtip("destroy");
                this.$.find(".spotifyLinkBlack").qtip({
                    content: {
                        text: "Play On Spotify"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                this.$.find(".ytLinkNA").qtip("destroy");
                this.$.find(".ytLinkNA").qtip({
                    content: {
                        text: "No Playable Song Found"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                this.$.find(".songAddColl").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            if( logged_in ){
                                // is song in collection
                                var db_id = e.currentTarget.dataset["id"];

                                if( this.parent.collectionView.inCollection(db_id) ){
                                    EventPipe.trigger(Constants.collectionView + "-removeFromCollection", db_id);
                                }
                                else{
                                    var obj = this.displaySongs.findWhere({db_id: db_id}).toJSON();

                                    // Because we saved it in the backend in seconds and js gives us ms
                                    obj.is_in_collection = parseInt(new Date().getTime() / 1000);

                                    EventPipe.trigger(Constants.collectionView + "-addToCollection", obj);
                                }
                            }
                            else{
                                Util.alert("Please login to save songs!");
                            }
                        }.bind(this));

                        $(el).qtip({
                            content: {
                                text: "Add To Collection"
                            },
                            position: {
                                my: "bottom center",
                                at: "top center"
                            },
                            style: {
                                classes: "qtip-tipsy"
                            },
                            hide: {
                                fixed: true,
                                delay: 100
                            }
                        });
                    }
                }.bind(this));
            }
        },

        createNewDetectiveFromExclude: function(name, db_id, artist_id){
            var params = {};

            params.image = this.activeDetective.image;

            if( !Util.validateDetectiveName(name) ){
                Util.hideLoadingIndicator();
                Util.alert("Wrong Detective Name format! Choose a name between 0 and 127 Characters!");
                return;
            }

            params.name = name;
            params.artists = [];

            this.activeDetective.artists.forEach(function(el){
                params.artists.push(el.id + "," + el.distance);
            });

            params.artists = params.artists.join(";");

            if( params.artists.length == 0 ){
                params.artists = "";
            }

            if( this.activeDetective.min_song_pop ){
                params.mnsp = this.activeDetective.min_song_pop;
            }

            if( this.activeDetective.max_song_pop ){
                params.mxsp = this.activeDetective.max_song_pop;
            }

            if( this.activeDetective.min_artist_pop ){
                params.mnap = this.activeDetective.min_artist_pop;
            }

            if( this.activeDetective.max_artist_pop ){
                params.mxap = this.activeDetective.max_artist_pop;
            }

            if( this.activeDetective.min_release_date ){
                params.mnrd = this.activeDetective.min_release_date;
            }

            if( this.activeDetective.max_release_date ){
                params.mxrd = this.activeDetective.max_release_date;
            }

            if( this.activeDetective.exclude_remix ){
                params.exr = this.activeDetective.exclude_remix;
            }
            if( this.activeDetective.exclude_acoustic ){
                params.exa = this.activeDetective.exclude_acoustic;
            }
            if( this.activeDetective.exclude_collection ){
                params.exc = this.activeDetective.exclude_collection;
            }

            if( artist_id ){
                params.exar = [artist_id];
                params.exar = params.exar.join(",");

                if( params.exar.length == 0 ){
                    params.exar = "false";
                }
            }
            else{
                params.exar = "false";
            }

            if( db_id ){
                params.exs = [db_id];
                params.exs = params.exs.join(",");

                if( params.exs.length == 0 ){
                    params.exs = "false";
                }
            }
            else{
                params.exs = "false";
            }

            $.ajax({
                url: Constants.baseUrl,
                method: "POST",
                data: "a=createdetective&" + $.param(params),
                xhrFields:{'withCredentials': true}
            }).done(function(data){
                Util.hideLoadingIndicator();

                if( data.length != 13 ){
                    Util.alert("Error!");
                }
                else{
                    Util.alert("New detective " + name + " created!");
                    GlobalCollections.detectives.fetch({xhrFields:{'withCredentials': true}});

                    // Excute Detective
                    EventPipe.trigger(Constants.contentView + "-showExploreView");
                    EventPipe.trigger(Constants.exploreView + "-exploreDetective", {uuid: data, name: params.name, image: (this.editMode) ? params.image : Constants.imageUrl}, true);
                }
            }.bind(this));
        },

        excludeSong: function(db_id){
            if( logged_in ){
                if( parseInt(this.activeDetective.owner_id) == 0 ){
                    Util.prompt("Detective name...", function(name){
                        Util.showLoadingIndicator();
                        this.createNewDetectiveFromExclude(name, db_id);
                    }.bind(this), "Create a new detective", this.activeDetective.name);
                }
                else{
                    if( this.displaySongs.findWhere({db_id: db_id.toString()}) ){
                        $.ajax({
                            url: Constants.baseUrl + "?a=excludefromdetective&uuid=" + this.activeDetective.uuid + "&song_id=" + db_id,
                            xhrFields:{'withCredentials': true}
                        });

                        this.displaySongs.remove(this.displaySongs.findWhere({db_id: db_id.toString()}));

                        EventPipe.trigger(Constants.playerView + "-removeFromSongQueueDetective", {uuid: this.activeDetective.uuid, ids: [db_id]});
                        GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid}).excludeSong(db_id.toString());

                        // Delete Song Div
                        this.$.find(".songDisplay > [data-id='" + db_id + "'] .songExcludeBtn").qtip('hide');
                        this.$.find(".songDisplay > [data-id='" + db_id + "']").remove();
                    }
                }
            }
            else{
                Util.alert("Please login to exclude songs!");
            }
        },

        excludeArtist: function(artist_id){
            if( logged_in ){
                if( parseInt(this.activeDetective.owner_id) == 0 ){
                    Util.prompt("Detective name...", function(name){
                        Util.showLoadingIndicator();
                        this.createNewDetectiveFromExclude(name, null, artist_id);
                    }.bind(this), "Create a new detective", this.activeDetective.name);
                }
                else{
                    if( this.displaySongs.where({db_artist_id: artist_id.toString()}) ){
                        $.ajax({
                            url: Constants.baseUrl + "?a=excludefromdetective&uuid=" + this.activeDetective.uuid + "&artist_id=" + artist_id,
                            xhrFields:{'withCredentials': true}
                        });

                        EventPipe.trigger(Constants.playerView + "-removeFromSongQueueDetective", {uuid: this.activeDetective.uuid, ids: this.displaySongs.where({db_artist_id: artist_id.toString()}).map(function(el){
                            return el.get("db_id");
                        })});

                        this.displaySongs.remove(this.displaySongs.where({db_artist_id: artist_id.toString()}));
                        GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid}).excludeArtist(artist_id.toString());

                        // Delete Song Div
                        this.$.find(".songDisplay [data-artist='" + artist_id + "']").parent().parent().find(".songExcludeBtn").qtip('hide');
                        this.$.find(".songDisplay [data-artist='" + artist_id + "']").parent().parent().remove();
                    }
                }
            }
            else{
                Util.alert("Please login to exclude artists!");
            }
        },

        assignHandlers: function(){
            this.$.find(".exEdit,.exImageWrapper").click(function(){
                EventPipe.trigger(Constants.contentView + "-editDetective", {uuid: this.activeDetective.uuid, lastView: Constants.exploreView});
            }.bind(this));

            this.$.find(".exEdit").qtip({
                content: {
                    text: "Edit Detective"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$.find("#createTrackset").click(function(){
                EventPipe.trigger(Constants.playerView + "-changeSongQueue", this.displaySongs.toJSON());
                EventPipe.trigger(Constants.playerView + "-playSpotifyTrackset");
            }.bind(this)).qtip({
                content: {
                    text: "Play Spotify Trackset"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$.find(".ev-refresh").click(function(){
                //EventPipe.trigger(Constants.exploreView + "-exploreDetective", this.activeDetective, true);
                GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid}).onChangeFinished();
            }.bind(this)).qtip({
                content: {
                    text: "Refresh Detective"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            if( login_mode == Constants.loginMode.spotifyLogin ){
                this.$.find(".createPlaylist").click(function(){
                    User.createSpotifyPlaylist(this.displaySongs.toJSON(), this.activeDetective.uuid);
                }.bind(this)).qtip({
                    content: {
                        text: "Create Spotify Playlist From Songs"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true
                    }
                });
            }
            else{
                this.$.find(".createPlaylist").hide();
            }
        },

        loadMore: function(playLastSong){
            if( !this.endOfDetective ){
                GlobalCollections.detectives_detail.findWhere({uuid: this.activeDetective.uuid}).getNextSongs(this.displaySongs.toJSON().length, playLastSong);
            }
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();
                this.songTemplate = Handlebars.compile($("#SongTemplate").html());
                this.exHoverTemplate = Handlebars.compile($("#excludeHoverTemplate").html());
                this.exploreArtistTemplate = Handlebars.compile($("#exploreArtistTemplate").html());

                this.$.append(this.template);

                this.$image = this.$.find(".exDetectiveImg");
                this.$name = this.$.find(".exDetectiveName");

                this.$artistContainer = this.$.find(".exArtistsContainer");
                this.$optionsContainer = this.$.find(".exOptionsContainer");

                this.assignHandlers();

                this.rendered = true;
            }

            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Collection View
     * @type {void|*}
     */
    var CollectionView = Backbone.View.extend({
        templateId: "CollectionTemplate",
        sid: Constants.collectionView,

        lastSongIndexRendered: 0,
        loadAmount: 40,

        collection: new (Backbone.Collection.extend({
            url: Constants.baseUrl + "?a=getcompletecollection",
            fetched: false
        }))(),

        refreshWanted: true,
        currentFilter: null,
        currentSort: null,

        sort: {
            ARTIST_DESC: 1,
            ARTIST_ASC: 2,
            TITLE_DESC: 3,
            TITLE_ASC: 4,
            ADDED_DESC: 5,
            ADDED_ASC: 6
        },

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;

            if( logged_in ){
                this.collection.fetch({xhrFields:{'withCredentials': true}});
            }

            this.registerEvents();
        },

        registerEvents: function(){
            this.collection.on("sync", function(){
                this.collection.fetched = true;
                this.refreshWanted = true;
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songNotAvailable", function(id){
                this.$.find(".collectionTable tr[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkNA");

                this.$.find(".ytLinkNA").qtip("destroy");
                this.$.find(".ytLinkNA").qtip({
                    content: {
                        text: "No Playable Song Found"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlaying", function(id, currentlyPlaying){
                this.$.find(".collectionTable td").removeClass("playing");
                this.$.find(".collectionTable .ytLinkPause").removeClass("ytLinkPause");

                this.$.find(".collectionTable tr[data-id='" + id + "'] > td").addClass("playing");

                if( currentlyPlaying ){
                    this.$.find(".collectionTable tr[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkPause");
                }
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingPause", function(id){
                this.$.find(".playing .ytLinkPlay").removeClass("ytLinkPause");
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingResume", function(id){
                this.$.find(".playing .ytLinkPlay").addClass("ytLinkPause");
            }.bind(this));

            EventPipe.on(Constants.collectionView + "-addToCollection", function(obj){
                this.collection.add(obj);
                this.renderSongs(0);

                $.ajax({
                    url: Constants.baseUrl + "?a=love&id=" + obj.db_id,
                    xhrFields:{'withCredentials': true}
                });

                this.syncCollectionIcons();
            }.bind(this));

            EventPipe.on(Constants.collectionView + "-removeFromCollection", function(id){
                var obj = this.collection.findWhere({db_id: id});

                this.collection.remove(obj);
                this.renderSongs(0);

                $.ajax({
                    url: Constants.baseUrl + "?a=delete&id=" + obj.toJSON().db_id,
                    xhrFields:{'withCredentials': true}
                });

                this.syncCollectionIcons();
            }.bind(this));
        },

        assignHandlers: function(){
            this.$.find(".cv-search").on('input', function(e){
                this.currentFilter = $(e.currentTarget).val()

                this.renderSongs(0);
            }.bind(this));

            this.$.find(".createTrackset").click(function(){
                if( this.collection.length ){
                    EventPipe.trigger(Constants.playerView + "-changeSongQueue", this.collection.toJSON());
                    EventPipe.trigger(Constants.playerView + "-playSpotifyTrackset");
                }
                else{
                    Util.alert("No songs in collection!");
                }
            }.bind(this)).qtip({
                content: {
                    text: "Play Spotify Trackset"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.$artistTblHdr.click(function(){
                this.$artistTblHdr.find("span").removeClass("arrow-up arrow-down");
                this.$titleTblHdr.find("span").removeClass("arrow-up arrow-down");
                this.$addedTblHdr.find("span").removeClass("arrow-up arrow-down");

                if( this.currentSort == this.sort.ARTIST_DESC ){
                    this.currentSort = this.sort.ARTIST_ASC;
                    this.$artistTblHdr.find("span").addClass("arrow-up");
                }
                else{
                    this.currentSort = this.sort.ARTIST_DESC;
                    this.$artistTblHdr.find("span").addClass("arrow-down");
                }

                this.renderSongs(0);
            }.bind(this));

            this.$titleTblHdr.click(function(){
                this.$artistTblHdr.find("span").removeClass("arrow-up arrow-down");
                this.$titleTblHdr.find("span").removeClass("arrow-up arrow-down");
                this.$addedTblHdr.find("span").removeClass("arrow-up arrow-down");

                if( this.currentSort == this.sort.TITLE_DESC ){
                    this.currentSort = this.sort.TITLE_ASC;
                    this.$titleTblHdr.find("span").addClass("arrow-up");
                }
                else{
                    this.currentSort = this.sort.TITLE_DESC;
                    this.$titleTblHdr.find("span").addClass("arrow-down");
                }

                this.renderSongs(0);
            }.bind(this));

            this.$addedTblHdr.click(function(){
                this.$artistTblHdr.find("span").removeClass("arrow-up arrow-down");
                this.$titleTblHdr.find("span").removeClass("arrow-up arrow-down");
                this.$addedTblHdr.find("span").removeClass("arrow-up arrow-down");

                if( this.currentSort == this.sort.ADDED_DESC ){
                    this.currentSort = this.sort.ADDED_ASC;
                    this.$addedTblHdr.find("span").addClass("arrow-up");
                }
                else{
                    this.currentSort = this.sort.ADDED_DESC;
                    this.$addedTblHdr.find("span").addClass("arrow-down");
                }

                this.renderSongs(0);
            }.bind(this));
        },

        syncCollectionIcons: function(){
            $(".songAddColl").each(function(idx, el){
                if( this.inCollection(el.dataset["id"]) ){
                    $(el).addClass("songLoved");
                }
                else{
                    $(el).removeClass("songLoved");
                }
            }.bind(this));

            EventPipe.trigger(Constants.playerView + "-setLoveIcon");
        },

        inCollection: function(id){
            return (this.collection.findWhere({db_id: id})) && true;
        },

        renderSongs: function(startIdx){
            if( this.rendered ){
                if( !startIdx ){
                    startIdx = 0;

                    if(!this.currentSort){
                        this.currentSort = this.sort.ADDED_DESC;
                    }

                    this.collection.comparator = function(a,b){
                        switch(this.currentSort) {
                            case this.sort.ADDED_DESC:
                                return (parseInt(a.get("is_in_collection")) < parseInt(b.get("is_in_collection"))) ? 1 : -1;
                            case this.sort.ADDED_ASC:
                                return (parseInt(a.get("is_in_collection")) < parseInt(b.get("is_in_collection"))) ? -1 : 1;
                            case this.sort.ARTIST_DESC:
                                return (a.get("artist_name") > b.get("artist_name")) ? 1 : -1;
                            case this.sort.ARTIST_ASC:
                                return (a.get("artist_name") > b.get("artist_name")) ? -1 : 1;
                            case this.sort.TITLE_DESC:
                                return (a.get("title") > b.get("title")) ? 1 : -1;
                            case this.sort.TITLE_ASC:
                                return (a.get("title") > b.get("title")) ? -1 : 1;
                            default:
                                return (parseInt(a.get("is_in_collection")) < parseInt(b.get("is_in_collection"))) ? 1 : -1;
                        }
                    }.bind(this);

                    this.collection.sort();
                }

                var t = "";
                var toIdx = startIdx + this.loadAmount;

                if( this.collection.length ){
                    this.collection.each(function(item, idx){
                        if( idx < toIdx && idx >= startIdx ){
                            var item = item.toJSON();

                            if( this.currentFilter ){
                                if( (item.artist_name + item.title).toLowerCase().indexOf(this.currentFilter.toLowerCase()) == -1 ){
                                    return;
                                }
                            }

                            item.is_in_collection = new Date(item.is_in_collection * 1000).toLocaleDateString();
                            item.isNA = (GlobalCollections.ytSongCache[item.db_id] !== undefined && GlobalCollections.ytSongCache[item.db_id] == false) ? "ytLinkNA" : "";

                            item.artists = [];
                            item.artist_name.split(";;").forEach(function(el, idx){
                                var artist_id = 0;

                                if( idx == 0 ){
                                    artist_id = item.db_artist_id;
                                }
                                else if( idx == 1 ){
                                    artist_id = item.db_artist_id2;
                                }
                                else if( idx == 2 ){
                                    artist_id = item.db_artist_id3;
                                }

                                item.artists.push({
                                    artist_name: el,
                                    db_artist_id: artist_id
                                });
                            });

                            t += this.songTemplate(item);

                            this.lastSongIndexRendered = idx;
                        }
                    }.bind(this));
                }
                else{
                    t = '<tr><td class="centerText" colspan="5">No Songs in Collection</td></tr>';
                }

                // Append or reset
                if( !startIdx ){
                    this.$.find(".collectionTable tbody").html(t);
                }
                else{
                    this.$.find(".collectionTable tbody").append(t);
                }

                this.$.find(".ytLinkNA").qtip("destroy");
                this.$.find(".ytLinkNA").qtip({
                    content: {
                        text: "No Playable Song Found"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                // Add events
                this.$.find(".artistLink").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["artist"]);
                        });
                    }
                }.bind(this));

                this.$.find(".ytLink").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            if ( $(el).parent().hasClass("playing") ){
                                if( $(el).hasClass("ytLinkPause") ){
                                    EventPipe.trigger(Constants.playerView + "-stopSong");
                                }
                                else {
                                    EventPipe.trigger(Constants.playerView + "-resumeSong");
                                }
                            }
                            else{
                                if ( !$(el).hasClass("ytLinkNA") ){
                                    var songs = [];

                                    this.collection.toJSON().forEach(function(item){
                                        if( this.currentFilter ){
                                            if( (item.artist_name + item.title).toLowerCase().indexOf(this.currentFilter.toLowerCase()) != -1 ){
                                                songs.push(item);
                                            }
                                        }
                                        else{
                                            songs.push(item);
                                        }
                                    }.bind(this));

                                    EventPipe.trigger(Constants.playerView + "-changeSongQueue", songs);
                                    EventPipe.trigger(Constants.playerView + "-playYtSong", e.currentTarget.dataset["id"]);
                                }
                            }
                        }.bind(this));
                    }
                }.bind(this));

                this.$.find(".songExcludeBtn").each(function(idx, el){
                    var ev = $._data(el, 'events');

                    if(!ev || !ev.click){
                        $(el).click(function(e){
                            var db_id = e.currentTarget.dataset["id"];

                            $.ajax({
                                url: Constants.baseUrl + "?a=delete&id=" + db_id,
                                xhrFields:{'withCredentials': true}
                            });

                            var obj = this.collection.findWhere({db_id: db_id});
                            this.collection.remove(obj);
                            this.syncCollectionIcons();

                            this.renderSongs(0);
                        }.bind(this));
                    }
                }.bind(this)).qtip({
                    content: {
                        text: "Remove From Collection"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                this.$.find(".spotifyLinkBlack").qtip({
                    content: {
                        text: "Play On Spotify"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });

                EventPipe.trigger(Constants.playerView + "-triggerCurrentSongEvents");
            }
        },

        loadMore: function(){
            this.renderSongs(this.lastSongIndexRendered + 1);
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();
                this.songTemplate = Handlebars.compile($("#SongTemplateCollection").html());

                this.$.append(this.template);
                this.rendered = true;
                this.refreshWanted = true;

                this.$artistTblHdr = this.$.find(".artistTablHdr");
                this.$titleTblHdr = this.$.find(".titleTablHdr");
                this.$addedTblHdr = this.$.find(".addedTablHdr");

                this.assignHandlers();
            }

            if( this.refreshWanted ){
                this.renderSongs();
                this.refreshWanted = false;
            }

            EventPipe.trigger("router.navigate", Constants.routes.collection);
            this.$.show();
        },
        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Artist View
     * @type {void|*}
     */
    var ArtistView = Backbone.View.extend({
        templateId: "ArtistTemplate",
        sid: Constants.artistView,

        artistModelCollection: new Backbone.Collection,

        currentModel: null,
        currentFilter: null,

        sortBy: {
            popularity: 1,
            name: 2,
            release_date: 3
        },

        currentSort: 1,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);

            this.parent = obj.parent;
            this.registerEvents();
        },

        registerEvents: function(){
            EventPipe.on(Constants.playerView + "-songNotAvailable", function(id){
                this.$.find(".av-songDisplay > div[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkNA");

                this.$.find(".ytLinkNA").qtip("destroy");
                this.$.find(".ytLinkNA").qtip({
                    content: {
                        text: "No Playable Song Found"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true,
                        delay: 50
                    }
                });
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlaying", function(id, currentlyPlaying){
                this.$.find(".av-songDisplay > div").removeClass("playing");
                this.$.find(".av-songDisplay .ytLinkPause").removeClass("ytLinkPause");

                this.$.find(".av-songDisplay > div[data-id='" + id + "']").addClass("playing");

                if( currentlyPlaying ){
                    this.$.find(".av-songDisplay > div[data-id='" + id + "'] .ytLinkPlay").addClass("ytLinkPause");
                }
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingPause", function(id){
                this.$.find(".playing .ytLinkPlay").removeClass("ytLinkPause");
            }.bind(this));

            EventPipe.on(Constants.playerView + "-songPlayingResume", function(id){
                this.$.find(".playing .ytLinkPlay").addClass("ytLinkPause");
            }.bind(this));
        },

        assignHandlers: function(){
            this.$.find(".mobile-back > span").click(function(){
                EventPipe.trigger(Constants.contentView + "-clearOverlays");
            }.bind(this));

            this.$.find(".av-overlay").click(function(){
                this.hide();
            }.bind(this));

            this.$barTopSongs.click(function(){
                this.$songWrapper.show();
                this.$relatedWrapper.hide();

                this.$barTopSongs.addClass("mdt-selected");
                this.$barRelatedArtists.removeClass("mdt-selected");

                this.$filter.show();
                this.$sortBy.show();
            }.bind(this));

            this.$barRelatedArtists.click(function(){
                this.$songWrapper.hide();
                this.$relatedWrapper.show();

                this.$barTopSongs.removeClass("mdt-selected");
                this.$barRelatedArtists.addClass("mdt-selected");

                this.$filter.hide();
                this.$sortBy.hide();
            }.bind(this));

            this.$filter.find("input").on('input', function(e){
                this.currentFilter = $(e.currentTarget).val();
                this.renderArtist(this.currentModel);
            }.bind(this));

            require(["chosen.jquery.min"], function(){
                this.$sortBy.find("select").chosen({disable_search_threshold: 99, width: "150px"}).change(function(e){
                    this.currentSort = parseInt($(e.currentTarget).val());
                    this.renderArtist(this.currentModel);
                }.bind(this));
            }.bind(this));
        },

        loadArtist: function(id){
            var found = false;
            Util.showLoadingSlider();

            this.artistModelCollection.forEach(function(el){
                if( el.get("id") == id ){
                    this.renderArtist(el.toJSON());
                    found = true;
                }
            }.bind(this));

            if( !found ){
                $.ajax({
                    url: Constants.baseUrl + "?a=getartistdetails&artist_id=" + id
                }).done(function(data){
                    try{
                        var obj = JSON.parse(data);
                    }
                    catch(e){
                        alert(e);
                        return;
                    }

                    this.artistModelCollection.add(obj);
                    this.renderArtist(obj);

                    window.setTimeout(function(){
                        this.$content.find(".av-artist-header,.artist-wrapper").show();
                        this.$loader.hide();
                        Util.finishLoadingSlider();
                    }.bind(this), 500);
                }.bind(this));
            }
            else{
                window.setTimeout(function(){
                    this.$content.find(".av-artist-header,.artist-wrapper").show();
                    this.$loader.hide();
                    Util.finishLoadingSlider();
                }.bind(this), 500);
            }
        },

        compare: function(a,b) {
            if( this.currentSort == this.sortBy.popularity ){
                if (parseInt(a.popularity) > parseInt(b.popularity))
                    return -1;
                else if (parseInt(a.popularity) < parseInt(b.popularity))
                    return 1;
                else{
                    if (a.title < b.title)
                        return -1;
                    else if (a.title > b.title)
                        return 1;
                }
            }
            else if( this.currentSort == this.sortBy.name ){
                if (a.title < b.title)
                    return -1;
                else if (a.title > b.title)
                    return 1;
                else
                    return 0;
            }
            else if( this.currentSort == this.sortBy.release_date ){
                if (parseInt(a.release_date) > parseInt(b.release_date))
                    return -1;
                else if (parseInt(a.release_date) < parseInt(b.release_date))
                    return 1;
                else{
                    if (a.title < b.title)
                        return -1;
                    else if (a.title > b.title)
                        return 1;
                }
            }
        },

        renderArtist: function(model){
            this.currentModel = model;

            var image = this.currentModel.info.image;

            if( !image ){
                image = Constants.bigImageNotFoundUrl;
            }

            if( !this.currentModel.info.image_background ){
                this.currentModel.info.image_background = Constants.backgroundImageNotFoundUrl;
            }

            this.$.find(".av-artist-header").css("background-image", "url('" + this.currentModel.info.image_background + "')");

            this.$.find(".av-artist-image").attr("src", image);

            this.$.find(".av-artist-name").html(this.currentModel.info.name);
            this.$genreWrapper.html("");
            this.$songWrapper.html("");
            this.$relatedWrapper.html("");

            this.currentModel.genres.forEach(function(el){
                this.$genreWrapper.append(this.genreTemplate(el));
            }.bind(this));

            this.currentModel.tracks.sort(this.compare.bind(this));
            this.currentModel.tracks.forEach(function(el){
                if( this.currentFilter && (el.title + " " + el.artist_name).toLowerCase().indexOf(this.currentFilter.toLowerCase()) == -1 ){
                    return;
                }

                el.in_collection = (this.parent.collectionView.inCollection(el.db_id)) ? "songLoved" : "";

                el.popularity = parseInt(el.popularity);
                el.popularity_text = Util.getSongPopularityLongString(el.popularity);
                el.popularity_color = parseInt((el.popularity/100.0) * 255);

                el.isNA = (GlobalCollections.ytSongCache[el.db_id] !== undefined && GlobalCollections.ytSongCache[el.db_id] == false) ? "ytLinkNA" : "";

                this.$songWrapper.append(this.songTemplate(el));
            }.bind(this));

            this.$.find(".spotifyLinkBlack").qtip({
                content: {
                    text: "Play On Spotify"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".ytLinkNA").qtip("destroy");
            this.$.find(".ytLinkNA").qtip({
                content: {
                    text: "No Playable Song Found"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true,
                    delay: 50
                }
            });

            this.$.find(".songReleaseDate").each(function(idx, el){
                $(el).html(new Date($(el).data().releaseDate * 1000).toLocaleDateString());
            }.bind(this));

            this.$.find(".songHotness").each(function(idx, el){
                $(el).qtip({
                    content: {
                        text: $(el).data().text
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true
                    }
                });
            }.bind(this));

            this.$.find(".ytLink").each(function(idx, el){
                $(el).click(function(e){
                    if ( $(el).parent().parent().hasClass("playing") ){
                        if( $(el).hasClass("ytLinkPause") ){
                            EventPipe.trigger(Constants.playerView + "-stopSong");
                        }
                        else {
                            EventPipe.trigger(Constants.playerView + "-resumeSong");
                        }
                    }
                    else{
                        if ( !$(el).hasClass("ytLinkNA") ){
                            var songs = [];

                            this.currentModel.tracks.sort(this.compare.bind(this));
                            this.currentModel.tracks.forEach(function(item){
                                if( this.currentFilter ){
                                    if( (item.artist_name + item.title).toLowerCase().indexOf(this.currentFilter.toLowerCase()) != -1 ){
                                        songs.push(item);
                                    }
                                }
                                else{
                                    songs.push(item);
                                }
                            }.bind(this));

                            EventPipe.trigger(Constants.playerView + "-changeSongQueue", songs);
                            EventPipe.trigger(Constants.playerView + "-playYtSong", e.currentTarget.dataset["id"]);
                        }
                    }
                }.bind(this));
            }.bind(this));

            this.$.find(".songAddColl").click(function(e){
                if( logged_in ){
                    // is song in collection
                    var db_id = e.currentTarget.dataset["id"];

                    if( this.parent.collectionView.inCollection(db_id) ){
                        EventPipe.trigger(Constants.collectionView + "-removeFromCollection", db_id);
                    }
                    else{
                        var obj = null;

                        this.currentModel.tracks.forEach(function(el){
                            if( el.db_id == db_id ){
                                obj = el;
                            }
                        });

                        // Because we saved it in the backend in seconds and js gives us ms
                        obj.is_in_collection = parseInt(new Date().getTime() / 1000);

                        EventPipe.trigger(Constants.collectionView + "-addToCollection", obj);
                    }
                }
                else{
                    Util.alert("Please login to save songs!");
                }
            }.bind(this)).qtip({
                content: {
                    text: "Add To Collection"
                },
                position: {
                    my: "bottom center",
                    at: "top center"
                },
                style: {
                    classes: "qtip-tipsy"
                },
                hide: {
                    fixed: true
                }
            });

            this.currentModel.related.forEach(function(el){
                if( !el.image ){
                    el.image = Constants.bigImageNotFoundUrl;
                }

                this.$relatedWrapper.append(this.aristRelatedTemplate(el));
            }.bind(this));

            this.$relatedWrapper.find("> div").click(function(e){
                EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["artist"]);
            });

            EventPipe.trigger(Constants.playerView + "-triggerCurrentSongEvents");
        },

        render: function(id){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html());
                this.genreTemplate = Handlebars.compile($("#ArtistGenreTemplate").html());
                this.songTemplate = Handlebars.compile($("#ArtistViewSongTemplate").html());
                this.aristRelatedTemplate = Handlebars.compile($("#ArtistRelatedTemplate").html());

                this.$.html(this.template());
                this.$content = this.$.find(".av-content");
                this.$genreWrapper = this.$.find(".av-genre-wrapper");
                this.$songWrapper = this.$.find(".av-songDisplay");
                this.$relatedWrapper = this.$.find(".av-related-artists");

                this.$barTopSongs = this.$.find(".av-top-songs-bar");
                this.$barRelatedArtists = this.$.find(".av-related-bar");


                this.$sortBy = this.$.find(".av-sort-by");
                this.$filter = this.$.find(".av-filter");

                this.$loader = this.$.find(".av-loader");

                this.assignHandlers();
                this.rendered = true;
            }

            this.$content.find(".av-artist-header,.artist-wrapper").hide();
            this.$loader.show();

            this.currentFilter = '';

            this.loadArtist(id);
            this.$barTopSongs.click();

            // clear filters
            this.$filter.find("input").val('');
            this.$.show();

            $("#content").css("overflow", "hidden");
            EventPipe.trigger("router.navigate", Constants.routes.showArtist + id);

            window.setTimeout(function(){
                this.$content.addClass("av-open");
            }.bind(this), 50);
        },
        hide: function(){
            if( this.rendered ){
                this.$content.removeClass("av-open");

                window.setTimeout(function(){
                    this.$.hide();
                }.bind(this), 500);
            }
            else{
                this.$.hide();
            }

            $("#content").css("overflow", "auto");
        }
    });

    /**
     * Currently Playing View
     * @type {void|*}
     */
    var CurrentlyPlayingView = Backbone.View.extend({
        templateId: "CurrentlyPlayingTemplate",
        sid: Constants.currentlyPlayingView,

        artists: new Backbone.Collection,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            this.parent = obj.parent;

            obj.targetEl.append(this.$);
            this.registerEvents();
        },

        registerEvents: function(){
            EventPipe.on(Constants.searchView + "-renderSongs", function(songs){
                this.renderSongs(songs);
            }.bind(this));
        },

        assignHandlers: function(){
            this.$.find(".mobile-back > span").click(function(){
                EventPipe.trigger(Constants.contentView + "-clearOverlays");
            }.bind(this));

            this.$.find(".cpv-overlay").click(function(){
                this.hide();
            }.bind(this));
        },

        renderSongs: function(songs){
            if( this.rendered ){
                this.$results.html("");

                var t = "";
                var max = 40;

                songs.forEach(function(el){
                    if( el.hide ){
                        return;
                    }

                    if( max >= 0 ){
                        el.artists = [];
                        el.artist_name.split(";;").forEach(function(item, idx){
                            var artist_id = 0;

                            if( idx == 0 ){
                                artist_id = el.db_artist_id;
                            }
                            else if( idx == 1 ){
                                artist_id = el.db_artist_id2;
                            }
                            else if( idx == 2 ){
                                artist_id = el.db_artist_id3;
                            }

                            el.artists.push({
                                artist_name: item,
                                db_artist_id: artist_id
                            });
                        });

                        t += this.songTemplate(el);
                    }

                    max--;
                }.bind(this));

                this.$results.html(t);

                this.$results.find(".artistLink").click(function(e){
                    EventPipe.trigger(Constants.contentView + "-openArtistView", e.currentTarget.dataset["artist"]);
                }.bind(this));

                this.$results.find(".songExcludeBtn").click(function(e){
                    EventPipe.trigger(Constants.playerView + "-removeSongFromQueue", [$(e.currentTarget).parent().data("id")]);
                    $(e.currentTarget).parent().hide();
                }.bind(this)).qtip({
                    content: {
                        text: "Remove From Queue"
                    },
                    position: {
                        my: "bottom center",
                        at: "top center"
                    },
                    style: {
                        classes: "qtip-tipsy"
                    },
                    hide: {
                        fixed: true
                    }
                });

                this.$results.find(".ytLink").click(function(e){
                    EventPipe.trigger(Constants.playerView + "-playYtSong", e.currentTarget.dataset["id"]);
                }.bind(this));
            }
        },

        render: function(id){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html());
                this.songTemplate = Handlebars.compile($("#CurrentlyPlayingSongTemplate").html());

                this.$.html(this.template());
                this.$content = this.$.find(".cpv-content");
                this.$results = this.$.find(".cpv-songs");

                this.assignHandlers();
                this.rendered = true;
            }

            $("#content").css("overflow", "hidden");

            this.$.show();

            window.setTimeout(function(){
                this.$content.addClass("cpv-open");
            }.bind(this), 50);
        },
        hide: function(){
            if( this.rendered ){
                this.$content.removeClass("cpv-open");

                window.setTimeout(function(){
                    this.$.hide();
                }.bind(this), 300);
            }
            else{
                this.$.hide();
            }

            $("#content").css("overflow", "auto");

            EventPipe.trigger(Constants.contentView + "-overlaysHidden");
        }
    });

    /**
     * Search View
     * @type {void|*}
     */
    var SearchView = Backbone.View.extend({
        templateId: "SearchTemplate",
        sid: Constants.searchView,

        artists: new Backbone.Collection,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;
            this.registerEvents();
        },

        registerEvents: function(){
            EventPipe.on(Constants.searchView + "-search", function(str){
                this.$results.html("");
                this.$.find(".sv-loader").show();
                this.$.find(".sv-artist-message").hide();
                this.artists.fetch({url: Constants.baseUrl + "?a=autocomplete&name=" + encodeURIComponent(str)});
            }.bind(this));

            EventPipe.on(Constants.searchView + "-focusMobileInput", function(){
                this.$searchField.focus();
            }.bind(this));

            this.artists.on("sync", function(){
                this.renderArtists();
                this.$.find(".sv-loader").hide();
            }.bind(this));
        },

        assignHandlers: function(){
            this.$.find(".mobile-back > span").click(function(){
                EventPipe.trigger(Constants.contentView + "-clearOverlays");
            }.bind(this));

            this.$.find(".sv-overlay").click(function(){
                this.hide();
            }.bind(this));

            this.searchInterval = null;

            this.$searchField.on('input', function(){
                if( this.searchInterval ){
                    clearTimeout(this.searchInterval);
                }

                this.searchInterval = window.setTimeout(function(){
                    this.searchInterval = null;
                    EventPipe.trigger(Constants.searchView + "-search", this.$searchField.val());
                }.bind(this), 250);
            }.bind(this));
        },

        renderArtists: function(){
            this.$results.html("");

            this.artists.toJSON().forEach(function(el){
                if( !el.image_small ){
                    el.image_small = Constants.smallImageNotFoundUrl;
                }

                this.$results.append(this.artistTemplate(el));
            }.bind(this));

            if ( this.artists.toJSON().length == 0 ){
                this.$.find(".sv-artist-message").show();
            }

            this.$results.find(".sv-artist").click(function(e){
                EventPipe.trigger(Constants.contentView + "-openArtistView" , e.currentTarget.dataset["id"]);
            }.bind(this));
        },

        render: function(id){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html());
                this.artistTemplate = Handlebars.compile($("#SearchArtistTemplate").html());

                this.$.html(this.template());
                this.$content = this.$.find(".sv-content");
                this.$results = this.$.find(".sv-results");
                this.$searchField = this.$.find(".search-artists");

                this.assignHandlers();
                this.rendered = true;
            }

            $("#content").css("overflow", "hidden");

            this.$.show();

            window.setTimeout(function(){
                this.$content.addClass("sv-open");
            }.bind(this), 50);
        },
        hide: function(){
            if( this.rendered ){
                this.$content.removeClass("sv-open");

                window.setTimeout(function(){
                    this.$.hide();
                }.bind(this), 300);
            }
            else{
                this.$.hide();
            }

            $("#content").css("overflow", "auto");
        }
    });

    /**
     * Password Change View
     * @type {void|*}
     */
    var PasswordChangeView = Backbone.View.extend({
        templateId: "passwordChangeTemplate",
        sid: Constants.passwordChangeView,

        rendered: false,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;
            this.registerEvents();
        },

        registerEvents: function(){

        },

        assignHandlers: function(){
            this.$.find(".changePasswordForm").on("submit", function(){
                if( this.$.find("input[name='password']").val().length < 4 || this.$.find("input[name='password']").val().length > 32 ){
                    Util.alert("Choose a password between 4 and 32 characters!");
                    return false;
                }

                if( this.$.find("input[name='password']").val() != this.$.find("input[name='confirm_password']").val() ){
                    Util.alert("Passwords do not match!");
                    return false;
                }

                Util.showLoadingIndicator();

                $.ajax({
                    url: Constants.baseUrl,
                    xhrFields:{'withCredentials': true},
                    method: "POST",
                    data: this.$.find(".changePasswordForm").serialize()
                }).done(function(response){
                    Util.hideLoadingIndicator();

                    if( !response || response == "" ){
                        Util.alert("Password successful changed! You can now log in with your new Password!");
                        return;
                    }

                    Util.alert(response);
                    EventPipe.trigger(Constants.contentView + "-showDetectivesView");
                });

                return false;
            }.bind(this));
        },

        render: function(options){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())({
                    reset_id: options.id,
                    reset_key: options.key
                });

                this.$.append(this.template);
                this.rendered = true;

                this.assignHandlers();
            }

            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Password Reset View
     * @type {void|*}
     */
    var PasswordResetView = Backbone.View.extend({
        templateId: "passwordResetTemplate",
        sid: Constants.passwordResetView,

        rendered: false,
        recaptchaWidgetId: null,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;
            this.registerEvents();
        },

        registerEvents: function(){

        },

        assignHandlers: function(){
            this.recaptchaWidgetId = grecaptcha.render(this.$.find(".g-recaptcha")[0], {
                'sitekey' : Constants.recaptchaKey
            });

            this.$.find(".resetPasswordForm").on("submit", function(){
                if( !Util.validateEmail(this.$.find(".register-input").val()) ){
                    Util.alert("Please enter a valid E-Mail!");
                    return false;
                }

                Util.showLoadingIndicator();

                $.ajax({
                    url: Constants.baseUrl,
                    xhrFields:{'withCredentials': true},
                    method: "POST",
                    data: this.$.find(".resetPasswordForm").serialize()
                }).done(function(response){
                    Util.hideLoadingIndicator();

                    if( !response || response == "" ){
                        Util.alert("You have received an E-Mail with instructions for the next steps!");
                        grecaptcha.reset(this.recaptchaWidgetId);
                        return;
                    }

                    grecaptcha.reset(this.recaptchaWidgetId);
                    Util.alert(response);
                }.bind(this));

                return false;
            }.bind(this));
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();

                this.$.append(this.template);
                this.rendered = true;

                this.assignHandlers();
            }

            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Register View
     * @type {void|*}
     */
    var RegisterView = Backbone.View.extend({
        templateId: "registerTemplate",
        sid: Constants.registerView,

        rendered: false,
        recaptchaWidgetId: null,

        initialize: function(obj){
            this.$ = jQuery('<div/>', {
                id: this.sid
            });

            obj.targetEl.append(this.$);
            this.parent = obj.parent;
            this.registerEvents();
        },

        registerEvents: function(){

        },

        assignHandlers: function(){
            this.recaptchaWidgetId = grecaptcha.render(this.$.find(".g-recaptcha")[0], {
                'sitekey' : Constants.recaptchaKey
            });

            this.$.find("#registerForm").on("submit", function(){
                if( !Util.validateUsername(this.$.find("input[name='username']").val()) || this.$.find("input[name='username']").val().length < 4 || this.$.find("input[name='username']").val().length > 18 ){
                    Util.alert("Choose a username between 4 and 18 characters! Don't use special chars and no spaces!");
                    return false;
                }

                if( this.$.find("input[name='password']").val().length < 4 || this.$.find("input[name='password']").val().length > 32 ){
                    Util.alert("Choose a password between 4 and 32 characters!");
                    return false;
                }

                if( this.$.find("input[name='password']").val().value != this.$.find("input[name='confirm_password']").val().value ){
                    Util.alert("Passwords do not match!");
                    return false;
                }

                if( !Util.validateEmail(this.$.find("input[name='email']").val()) ){
                    Util.alert("Please enter a valid E-Mail!");
                    return false;
                }

                if( !this.$.find("#agreePolicy").prop('checked') ){
                    Util.alert("Please agree to our Terms and Conditions!");
                    return false;
                }

                Util.showLoadingIndicator();

                $.ajax({
                    url: Constants.baseUrl,
                    xhrFields:{'withCredentials': true},
                    method: "POST",
                    data: this.$.find('#registerForm').serialize()
                }).done(function(response){
                    if( !response || response == "" ){
                        // Now save the temporary Detectives!
                        var tempDetectives = (localStorage && localStorage.getItem("tempDetectives")) ? JSON.parse(localStorage.getItem("tempDetectives")) : [];

                        tempDetectives.forEach(function(el){
                            // Don't send the unessecary stuff
                            delete el.artist_model;
                            delete el.uuid;

                            $.ajax({
                                url: Constants.baseUrl,
                                method: "POST",
                                data: "a=createdetective&" + $.param(el),
                                xhrFields:{'withCredentials': true},
                                async: false
                            });
                        });

                        localStorage.removeItem("tempDetectives");
                        window.location.reload();
                        return;
                    }

                    Util.hideLoadingIndicator();

                    grecaptcha.reset(this.recaptchaWidgetId);
                    Util.alert(response);
                }.bind(this));

                return false;
            }.bind(this));
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#" + this.templateId).html())();

                this.$.append(this.template);
                this.rendered = true;

                this.assignHandlers();
            }

            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Content View
     * @type {void|*}
     */
    var ContentView = Backbone.View.extend({
        targetEl: null,
        sid: Constants.contentView,

        chartsView: null,
        exploreView: null,
        artistView: null,
        playerView: null,

        views: [],
        activeView: null,
        activeOverlay: null,

        currentDetectivesView: null,
        prohibitScroll: false,

        initialize: function(obj){
            this.targetEl = obj.targetEl;

            this.chartsView = new ChartsView({targetEl: this.targetEl, parent: this});
            this.exploreView = new ExploreView({targetEl: this.targetEl, parent: this});
            this.collectionView = new CollectionView({targetEl: this.targetEl, parent: this});
            this.artistView = new ArtistView({targetEl: this.targetEl, parent: this});
            this.showDetectivesView = new ShowDetectivesView({targetEl: this.targetEl, parent: this});
            this.modifyDetectivesView = new ModifyDetectivesView({targetEl: this.targetEl, parent: this});
            this.searchView = new SearchView({targetEl: this.targetEl, parent: this});
            this.currentlyPlayingView = new CurrentlyPlayingView({targetEl: this.targetEl, parent: this});
            this.registerView = new RegisterView({targetEl: this.targetEl, parent: this});
            this.passwordResetView = new PasswordResetView({targetEl: this.targetEl, parent: this});
            this.passwordChangeView = new PasswordChangeView({targetEl: this.targetEl, parent: this});
            this.mobileSidebarView = new MobileSidebarView({targetEl: $("#mobileSidebar"), parent: this});

            this.views = [this.passwordChangeView, this.passwordResetView, this.registerView, this.searchView, this.artistView, this.chartsView, this.exploreView, this.collectionView, this.showDetectivesView, this.modifyDetectivesView];
            this.overlays = [this.searchView, this.artistView, this.currentlyPlayingView, this.mobileSidebarView];

            this.$content = $("#content");
            this.$loginFormTemplate = $("#loginFormTemplate");

            $(".nav .explore").addClass("selected");
            this.render(this.showDetectivesView);

            this.assignHandlers();
            this.registerEvents();
        },

        registerEvents: function(){
            EventPipe.on(Constants.contentView + "-toggleExploreView", function(){
                this.currentDetectivesView = (this.currentDetectivesView == this.showDetectivesView) ? this.exploreView : this.showDetectivesView;
                this.render(this.currentDetectivesView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showExploreView", function(){
                this.currentDetectivesView = this.exploreView;
                this.render(this.currentDetectivesView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showChartsView", function(){
                $(".nav .selected").removeClass("selected");
                $(".nav .charts").addClass("selected");
                this.render(this.chartsView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showCollectionView", function(){
                if( logged_in ){
                    $(".nav .selected").removeClass("selected");
                    $(".nav .collection").addClass("selected");
                    this.render(this.collectionView);
                }
                else{
                    Util.alert("Please log in to view your collection!");
                }
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showDetectivesView", function(){
                $(".nav .selected").removeClass("selected");
                $(".nav .explore").addClass("selected");
                this.currentDetectivesView = this.showDetectivesView;
                this.render(this.currentDetectivesView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-editDetective", function(uuid){
                this.render(this.modifyDetectivesView, uuid);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-createDetective", function(uuid){
                this.render(this.modifyDetectivesView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showRegisterView", function(uuid){
                this.render(this.registerView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showPasswordChangeView", function(options){
                this.render(this.passwordChangeView, options);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-showPasswordResetView", function(uuid){
                this.render(this.passwordResetView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-openSearchView", function(){
                this.renderOverlay(this.searchView);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-clearOverlays", function(){
                this.renderOverlay(null);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-openArtistView", function(id){
                this.renderOverlay(this.artistView, id);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-openCurrentlyPlayingView", function(id){
                this.renderOverlay(this.currentlyPlayingView, id);
            }.bind(this));

            EventPipe.on(Constants.contentView + "-toggleCurrentlyPlayingView", function(id){
                if( this.activeOverlay != this.currentlyPlayingView ){
                    this.renderOverlay(this.currentlyPlayingView, id);
                }
                else{
                    this.renderOverlay(null);
                }
            }.bind(this));

            EventPipe.on(Constants.contentView + "-overlaysHidden", function(){
                this.activeOverlay = null;
            }.bind(this));
        },

        assignMobileHandlers: function(){
            var self = this;

            $("#mobileHeader .logo-mobile > img").click(function(){
                this.render(this.showDetectivesView);
            }.bind(this));

            $("#mobileHeader .burger-menu-wrapper").on("tap mousedown",function(){
                if( $(this).find(".burger-menu").hasClass('open') ){
                    $(this).find(".burger-menu").toggleClass('open');
                    self.renderOverlay(null);
                }
                else{
                    $(this).find(".burger-menu").toggleClass('open');
                    self.renderOverlay(self.mobileSidebarView);
                }
            });

            $("#mobileHeader .player-btn").on("tap mousedown", function(){
                if( $(this).hasClass("slideDown") ){
                    $("#sidebar").removeClass("mb-hide");
                    $(this).removeClass("slideDown");

                    window.setTimeout(function(){
                        $("#playerView").removeClass("playerClosed");
                    }.bind(this), 50);
                }
                else{
                    $(this).addClass("slideDown");
                    $("#playerView").addClass("playerClosed");

                    window.setTimeout(function(){
                        $("#sidebar").addClass("mb-hide");
                    }, 400);
                }
            });

            $(".sdb-overlay").on("tap mousedown", function(){
                $("#mobileHeader .player-btn").addClass("slideDown");
                $("#playerView").addClass("playerClosed");

                window.setTimeout(function(){
                    $("#sidebar").addClass("mb-hide");
                }, 400);
            });
        },

        assignHandlers: function(){
            $("#sidebar .charts").click(function(){
                $(".nav .selected").removeClass("selected");
                $(".nav .charts").addClass("selected");

                this.render(this.chartsView);
            }.bind(this));

            $("#sidebar .explore").click(function(){
                $(".nav .selected").removeClass("selected");
                $(".nav .explore").addClass("selected");

                this.render(this.showDetectivesView);
            }.bind(this));

            $("#sidebar .collection").click(function(){
                if( logged_in ){
                    $(".nav .selected").removeClass("selected");
                    $(".nav .collection").addClass("selected");
                    this.render(this.collectionView);
                }
                else{
                    Util.alert("Please log in to view your collection!");
                }
            }.bind(this));

            if( logged_in ){
                $(".logout").click(function(){
                    User.logout();
                }).show();
            }
            else{
                $(".login").click(function(){
                    vex.open({
                        content: this.$loginFormTemplate.html(),
                        afterOpen: function($vexContent) {
                            $vexContent.find(".register-link").click(function(){
                                EventPipe.trigger('User-register');
                                vex.close($vexContent.data().vex.id);
                            });

                            $vexContent.find(".reset-password-link").click(function(){
                                EventPipe.trigger(Constants.contentView + "-showPasswordResetView");
                                vex.close($vexContent.data().vex.id);
                            });

                            return;
                        },
                        afterClose: function() {
                            return console.log('vexClose');
                        },
                        showCloseButton: false
                    });
                }.bind(this)).show();
            }

            $back_to_top = $('.cd-top');

            // Scrolling
            this.$content.scroll(function() {
                if(this.activeView && this.activeView.$ && this.$content.scrollTop() > 0 && (this.$content.scrollTop() == this.activeView.$.height() - this.$content.height())) {
                    if( !this.prohibitScroll && this.activeView.loadMore ){
                        this.activeView.loadMore();
                    }
                }

                (  this.$content.scrollTop() > 300 ) ? $back_to_top.addClass('cd-is-visible') : $back_to_top.removeClass('cd-is-visible cd-fade-out');
                if(  this.$content.scrollTop() > 1200 ) {
                    $back_to_top.addClass('cd-fade-out');
                }
            }.bind(this));

            //smooth scroll to top
            $back_to_top.on('click', function(event){
                event.preventDefault();
                this.$content.animate({
                        scrollTop: 0 ,
                    }, 700
                );
            }.bind(this));

            this.assignMobileHandlers();
        },

        renderOverlay: function(view, options){
            this.overlays.forEach(function(element){
                if( view != element ){
                    element.hide();
                }
            });

            // Hide Mobile Player
            $("#mobileHeader .player-btn").addClass("slideDown");
            $("#playerView").addClass("playerClosed");

            window.setTimeout(function(){
                $("#sidebar").addClass("mb-hide");
            }, 400);

            this.activeOverlay = view;

            if( view ){
                view.render(options);
            }
        },

        render: function(view, options){
            this.prohibitScroll = true;

            if( view != this.exploreView ){
                this.$content.scrollTop(0);
            }

            this.views.forEach(function(element){
                if( view != element ){
                    element.hide();
                }
            });

            this.overlays.forEach(function(element){
                element.hide();
            });

            this.activeView = view;

            if( view ){
                view.render(options);
            }

            this.prohibitScroll = false;
        }
    });

    /**
     * Mobile Sidebar
     */
    var MobileSidebarView = Backbone.View.extend({
        sid: Constants.mobileSidebarView,

        initialize: function(obj){
            this.$ = $("#" + this.sid);
            this.parent = obj.parent;
        },

        registerEvents: function(){

        },

        assignHandlers: function(){
            this.$.find(".msb-overlay").click(function(){
                $("#mobileHeader .burger-menu").toggleClass('open');
                this.hide();
            }.bind(this));

            this.$.find(".search").click(function(){
                $("#mobileHeader .burger-menu").toggleClass('open');
                EventPipe.trigger(Constants.contentView + "-openSearchView");
                EventPipe.trigger(Constants.searchView + "-focusMobileInput");
            }.bind(this));

            this.$.find(".charts").click(function(){
                this.$.find(".selected").removeClass("selected");
                this.$.find(".charts").addClass("selected");

                $("#mobileHeader .burger-menu").toggleClass('open');
                this.parent.render(this.parent.chartsView);
            }.bind(this));

            this.$.find(".explore").click(function(){
                this.$.find(".selected").removeClass("selected");
                this.$.find(".explore").addClass("selected");

                $("#mobileHeader .burger-menu").toggleClass('open');
                this.parent.render(this.parent.showDetectivesView);
            }.bind(this));

            this.$.find(".collection").click(function(){
                if( logged_in ){
                    this.$.find(".selected").removeClass("selected");
                    this.$.find(".collection").addClass("selected");

                    $("#mobileHeader .burger-menu").toggleClass('open');
                    this.parent.render(this.parent.collectionView);
                }
                else{
                    Util.alert("Please log in to view your collection!");
                }
            }.bind(this));

            if( logged_in ){
                this.$.find(".logout").click(function(){
                    User.logout();
                }).show();
            }
            else{
                this.$.find(".login").click(function(){
                    vex.open({
                        content: this.parent.$loginFormTemplate.html(),
                        afterOpen: function($vexContent) {
                            $vexContent.find(".register-link").click(function(){
                                EventPipe.trigger('User-register');
                                $("#mobileHeader .burger-menu").toggleClass('open');
                                vex.close($vexContent.data().vex.id);
                            });

                            $vexContent.find(".reset-password-link").click(function(){
                                EventPipe.trigger(Constants.contentView + "-showPasswordResetView");
                                $("#mobileHeader .burger-menu").toggleClass('open');
                                vex.close($vexContent.data().vex.id);
                            });

                            return;
                        },
                        afterClose: function() {
                            return console.log('vexClose');
                        },
                        showCloseButton: false
                    });
                }.bind(this)).show();
            }
        },

        render: function(){
            if( !this.rendered ){
                this.template = Handlebars.compile($("#mobileSidebarTemplate").html())();
                this.$.append(this.template);

                this.$content = this.$.find(".msb-content");

                this.rendered = true;
                this.assignHandlers();
            }

            $("#content").css("overflow", "hidden");

            this.$.show();

            window.setTimeout(function(){
                this.$content.addClass("msb-open");
            }.bind(this), 50);
        },

        hide: function(){
            if( this.rendered ){
                this.$content.removeClass("msb-open");

                window.setTimeout(function(){
                    this.$.hide();
                }.bind(this), 300);
            }
            else{
                this.$.hide();
            }

            $("#content").css("overflow", "auto");
        }
    });

    /**
     * Sidebar View
     * @type {void|*}
     */
    var SidebarView = Backbone.View.extend({
        sid: Constants.sidebarView,

        $searchField: null,
        player: null,

        initialize: function(obj){
            this.$ = $("#" + this.sid);
            this.$searchField = this.$.find("#search");

            this.player = new PlayerView({targetEl: this.$, parent: obj.contentView});

            // set content playerView to player
            obj.contentView.playerView = this.player;

            this.assignHandlers();
        },

        registerEvents: function(){

        },

        assignHandlers: function(){
            this.$searchField.focus(function(){
                EventPipe.trigger(Constants.contentView + "-openSearchView");
            }.bind(this));

            this.searchInterval = null;

            this.$searchField.on('input', function(){
                if( this.searchInterval ){
                    clearTimeout(this.searchInterval);
                }

                this.searchInterval = window.setTimeout(function(){
                    this.searchInterval = null;
                    EventPipe.trigger(Constants.searchView + "-search", this.$searchField.val());
                }.bind(this), 250);
            }.bind(this));
        },

        render: function(){
            this.$.show();
        },

        hide: function(){
            this.$.hide();
        }
    });

    /**
     * Initialize our App
     */
    $(document).ready(function(){
        var contentView = new ContentView({targetEl: $("#content")});
        var sidebarView = new SidebarView({contentView: contentView});

        // For Testing!!!
        window.___contentView = contentView;
        window.___sidebarView = sidebarView;
        window.___globalCollections = GlobalCollections;
    });
});