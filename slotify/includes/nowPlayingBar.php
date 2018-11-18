<?php
    $songQuery = mysqli_query($con, "SELECT id FROM songs ORDER BY RAND() LIMIT 10");

    $result = [];

    while($row = mysqli_fetch_array($songQuery)) {
        array_push($result, $row['id']);
    }

    $json = json_encode($result);
?>

<script>
    $(document).ready(function() {
        var newPlaylist = <?php echo $json; ?>;
        audioElement = new Audio();
        setTrack(newPlaylist[0], newPlaylist);
        updateVolumeProgressBar(audioElement.audio);
        
        $(".play").on("click", function() {
            play = true;
        });

        $(".controlButton.previous")

        $("#nowPlayingBarContainer").on("mousedown touchstart mousemove touchmove", function(e) {
            e.preventDefault();
        });
        
        $(".playbackBar .progressBar").mousedown(function(){
            mouseDown = true;
	    });

        $(".playbackBar .progressBar").mousemove(function(e){
            if(mouseDown) {
                timeFromOffset(e, this);
            }
        });

        $(".playbackBar .progressBar").mouseup(function(e) {
		    timeFromOffset(e, this);
        });
        
        $(".volumeBar .progressBar").mousedown(function(){
            mouseDown = true;
	    });

        $(".volumeBar .progressBar").mousemove(function(e){
            if(mouseDown) {
                var percentage = e.offsetX / $(this).width();
                if(percentage >= 0 && percentage <=1) {
                    audioElement.audio.volume = percentage;
                    currentVolume = audioElement.audio.volume;
                }
            }
        });

        $(".volumeBar .progressBar").mouseup(function(e) {
            var percentage = e.offsetX / $(this).width();
            if(percentage >= 0 && percentage <=1) {
                audioElement.audio.volume = percentage;
                currentVolume = audioElement.audio.volume;
            }
	    });

        $(document).mouseup(function() {
            mouseDown = false;
        });
    });

    function timeFromOffset(mouse, progressBar) {
        var percentage = mouse.offsetX / $(progressBar).width() * 100;
        var seconds = audioElement.audio.duration * (percentage / 100);
        audioElement.setTime(seconds);
    }

    function previousSong() {
        $(".playbackBar .progress").css("width", 0);

        if(currentIndex != 0) {
            currentIndex--;
        } else {
            currentIndex = currentPlaylist.length - 1;
        }

        var trackToPlay = shuffle ? shufflePlaylist[currentIndex] : currentPlaylist[currentIndex];
        setTrack(trackToPlay, currentPlaylist);
    }

    function nextSong() {

        $(".playbackBar .progress").css("width", 0);

        if(repeat) {
            audioElement.setTime(0);
            playSong();
            return;
        }


        if(currentIndex == currentPlaylist.length - 1) {
            currentIndex = 0;
        } else {
            currentIndex++;
        }
        
        var trackToPlay = shuffle ? shufflePlaylist[currentIndex] : currentPlaylist[currentIndex];
        setTrack(trackToPlay, currentPlaylist);
    }
    
    function setRepeat() {
        repeat = !repeat;
        var imageName = repeat ? "repeat-active.png" : "repeat.png";
        $(".controlButton.repeat img").attr("src", "assets/images/icons/" + imageName);
    }

    function setMute() {
        audioElement.audio.muted = !audioElement.audio.muted;
        if(audioElement.audio.muted) {
            audioElement.audio.volume = 0;
        } else {
            audioElement.audio.volume = currentVolume;
        }
        var imageName = audioElement.audio.muted ? "volume-mute.png" : "volume.png";
        $(".controlButton.volume img").attr("src", "assets/images/icons/" + imageName);
    }

    function unmuteByVolumeChange() {
        if(audioElement.audio.muted) {
            audioElement.audio.muted = false;
            $(".controlButton.volume img").attr("src", "assets/images/icons/volume.png");
        }
    }

    function setShuffle() {
        shuffle = !shuffle;
        var imageName = shuffle ? "shuffle-active.png" : "shuffle.png";
        $(".controlButton.shuffle img").attr("src", "assets/images/icons/" + imageName);

        if(shuffle) {
            shuffleArray(shufflePlaylist);
            currentIndex = shufflePlaylist.indexOf(audioElement.currentlyPlaying.id);
        } else {
            currentIndex = currentPlaylist.indexOf(audioElement.currentlyPlaying.id);
        }
    }

    function shuffleArray(a) {
        var j, x, i;
        for(i = a.length; i; i--) {
            j = Math.floor(Math.random() * i);
            x = a[i-1];
            a[i - 1] = a[j];
            a[j] = x;
        }
    }

    function setTrack(trackId, newPlaylist) {
        
        if(newPlaylist != currentPlaylist) {
            currentPlaylist = newPlaylist;
            shufflePlaylist = currentPlaylist.slice();
        }

        $.ajaxSetup(
            {
                async: false
            }
        );

        if(shuffle) {
            currentIndex = shufflePlaylist.indexOf(trackId);
        } else {
            currentIndex = currentPlaylist.indexOf(trackId);
        }
        // pauseSong();

        $.post("includes/handlers/ajax/getSongJson.php", { songId : trackId }, function(data) {

            var track = JSON.parse(data);
            $(".trackName span").text(track.title);
            
            $.post("includes/handlers/ajax/getArtistJson.php", { artistId : track.artist }, function(data) {
                var artist = JSON.parse(data);
                $(".artistName span").text(artist.name);
            });

            $.post("includes/handlers/ajax/getAlbumJson.php", { albumId : track.album }, function(data) {
                var album = JSON.parse(data);
                $(".albumLink img").attr("src", album.artworkPath);
            });

            audioElement.setTrack(track);
            // playSong();
        });
        if(play) {
            audioElement.play();
        }
    }

    function playSong() {

        if(audioElement.audio.currentTime == 0) {
            $.post("includes/handlers/ajax/updatePlays.php", { songId : audioElement.currentlyPlaying.id });
        }

        $(".controlButton.play").hide();
        $(".controlButton.pause").show();
        audioElement.play();
    }
    
    function pauseSong() {
        $(".controlButton.play").show();
        $(".controlButton.pause").hide();
        audioElement.pause();
        play = false;
    }

</script>

<div id="nowPlayingBarContainer">
    <div id="nowPlayingBar">
        <div id="nowPlayingLeft">
            <div class="content">
                <span class="albumLink">
                    <img src="assets/images/loader.gif" class="albumArtwork">
                </span>

            <div class="trackInfo">
                <span class="trackName">
                    <span></span>
                </span>

                <span class="artistName">
                    <span></span>
                </span>
            </div>

            </div>
        </div>

        <div id="nowPlayingCenter">
            <div class="content playerControls">

                <div class="buttons">
                    <button class="controlButton shuffle" title="Shuffle" onclick="setShuffle()">
                        <img src="assets/images/icons/shuffle.png" alt="Shuffle">
                    </button>

                    <button class="controlButton previous" title="Previous" onclick="previousSong()">
                        <img src="assets/images/icons/previous.png" alt="Previous">
                    </button>

                    <button class="controlButton play" title="Play" onclick="playSong()">
                        <img src="assets/images/icons/play.png" alt="Play">
                    </button>

                    <button class="controlButton pause" title="Pause" onclick="pauseSong()">
                        <img src="assets/images/icons/pause.png" alt="Pause">
                    </button>

                    <button class="controlButton next" title="Next" onclick="nextSong()">
                        <img src="assets/images/icons/next.png" alt="Next">
                    </button>

                    <button class="controlButton repeat" title="Repeat" onclick="setRepeat()">
                        <img src="assets/images/icons/repeat.png" alt="Repeat">
                    </button>
                </div>

                <div class="playbackBar">
                    <span class="progressTime current">0.00</span>
                    <div class="progressBar">
                        <div class="progressBarBg">
                            <div class="progress">
                                
                            </div>
                        </div>
                    </div>
                    <span class="progressTime remaining">0.00</span>
                </div>


            </div>
        </div>

        <div id="nowPlayingRight">
            <div class="volumeBar">
                <button class="controlButton volume" title="Volume" onclick="setMute()">
                    <img src="assets/images/icons/volume.png" alt="Volume">
                </button>

                <div class="progressBar" onclick="unmuteByVolumeChange()">
                    <div class="progressBarBg">
                        <div class="progress">
                            
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>