<?php
$pageTitle = "Album";
$navTab = "ALBUM";
$subNavPage = null;
$requireAdmin = false;
?>
<?php include('head.php'); ?>
<body>
<?php include('nav.php'); ?>

<!-- Content -->
<div id="content" class="container-fluid">
	<div class="container-fluid card mb-3 maxWidth-sm">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table" id="scoreTable">
					<thead>
						<th class="border-0">Team</th>
						<th class="border-0">Score</th>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
	<div id="album"></div>
</div>

<!-- Modal -->
<div id="albumModal" class="modal p-0" tabindex="-1" role="dialog">
	<div class="modal-dialog m-0 w-100 mw-100" role="document">
		<div class="modal-content">
			<div class="modal-header position-relative">
				<h5 class="modal-title text-center col">
					<span class="thumbnailIcon"></span>
					<span class="challengeName"></span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="modalCarousel" class="carousel slide col p-0" data-interval="false">
					<!-- Wrapper for slides -->
					<div class="carousel-inner"></div>

					<!-- Controls -->
					<a class="carousel-control-prev" href="#modalCarousel" role="button" data-slide="prev">
						<span class="carousel-control-prev-icon" aria-hidden="true"></span>
						<span class="sr-only">Previous</span>
					</a>
					<a class="carousel-control-next" href="#modalCarousel" role="button" data-slide="next">
						<span class="carousel-control-next-icon" aria-hidden="true"></span>
						<span class="sr-only">Next</span>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<!--  HTML Templates -->
<div class="templates" style="display:none">
	<div id="carouselSlide">
		<div class="carousel-item">
			<figure class="figure img-thumbnail m-0 w-100">
				<figcaption class="figure-caption text-center teamName"></figcaption>
				<img class="figure-img img-fluid w-100">
			</figure>
		</div>
	</div>
	<div id="challengeCard">
		<div class="container-fluid card mb-3">
			<div class="card-body">
				<h5 class="card-title challengeTitle"></h5>
				<!-- row-cols-X = X columns per row-->
				<div class="row row-cols-3 thumbnails"></div>
			</div>
		</div>
	</div>
	<div id="emptyCard">
		<div class="container-sm card mb-3 maxWidth-sm">
			<div class="card-body">
				<div class="text"></div>
			</div>
		</div>
	</div>
	<div id="thumbnail">
		<div class="col mb-2 pl-0 pr-2 thumbnailWrapper">
			<span class="position-absolute thumbnailIcon"></span>
			<a class="thumbnailLink" data-toggle="modal" data-target="#albumModal" aria-label="Expand image">
				<figure class="figure img-thumbnail m-0">
					<img class="figure-img img-fluid">
					<figcaption class="figure-caption text-center"></figcaption>
				</figure>
			</a>
		</div>
	</div>
	<div id="honoredIcon">
		<i class="fa fa-star"></i>
	</div>
	<div id="winnerIcon">
		<i class="fa fa-trophy position-relative">
			<span class="position-absolute font-weight-bold number small">1</span>
		</i>
	</div>
</div>

<!-- JavaScript -->
<script type="text/javascript">
	$(document).ready(() => {
		const $scoreTable = $('#scoreTable');
		const $album = $('#album');
		const $modal = $('#albumModal');
		const $modalChallengeName = $modal.find('.challengeName');
		const $modalIcon = $modal.find('.thumbnailIcon');
		const modalId = 'modalCarousel';
		const $modalCarousel = $('#' + modalId);
		const $modalSlidesWrapper = $modalCarousel.find('.carousel-inner');
		let uploadsByChallenge;
		let slideIndex = {};

		trackStats("LOAD/fun/game/album");
		loadData().done(render);

		function render() {
			uploadsByChallenge = getUploadsByChallenge();
			renderScoreTable();
			renderAlbum();
			renderModal();
			setupHandlers();
			probeForTeamsChanges();
		}

		function probeForTeamsChanges() {
			// Reload teams and render the score table every 10 seconds
			setInterval(() => loadTeams().always(renderScoreTable), 10000);
		}

		function renderScoreTable() {
			$scoreTable.find('tbody').empty();
			if (teams.length === 0) {
				$scoreTable.append("Teams have not been setup.");
			} else {
				_.each(getTeamsSortedByScore(), (team) => {
					$scoreTable.append(scoreRow(team));
				});
			}
		}

		function renderAlbum() {
			$album.empty();
			if (_.isEmpty(uploadsByChallenge)) {
				// Show message for no content
				$album.append(emptyCard("No published albums."));
			} else {
				// Render each challenge
				_.each(uploadsByChallenge, (uploads, challengeIndex) => {
					$album.append(challengeCard(uploads, challengeIndex));
				});
			}
		}

		function renderModal() {
			// Reset the slides
			$modalSlidesWrapper.empty();
			slideIndex = {};

			// Render each slide
			let isFirst = true;
			let slideNum = 0;
			_.each(uploadsByChallenge, (uploads, challengeIndex) => {
				_.each(uploads, (upload) => {
					const url = getDownloadLinkForUpload(upload);
					const teamName = getTeamName(upload.teamIndex);
					const isActive = isFirst;
					isFirst = false;
					$modalSlidesWrapper.append(carouselSlide(modalId, url, teamName, isActive, {file: upload.file}));

					// Keep a mapping of key-value (file-slideNum) for look-up later
					slideIndex[upload.file] = slideNum++;
				});
			});
		}

		function setupHandlers() {
			// Set the slide when opening the modal
			$('.thumbnailLink').click((e) => {
				const $link = $(e.currentTarget);
				const file = $link.attr('file');
				const slideNum = slideIndex[file];
				$modalCarousel.carousel(slideNum);
			});

			// Update the modal on slide transition
			$modalCarousel.off('slide.bs.carousel').on('slide.bs.carousel', (e) => {
				const $slide = $(e.relatedTarget);
				const file = $slide.attr('file');
				const upload = getUploadByFile(file);
				$modalChallengeName.text(getChallengeName(upload.challengeIndex));
				$modalIcon.html(thumbnailIcon(upload));
			});

			if ($album.find('.text').text().trim() !== 'No published albums.') {
				// Trigger first transition to enable mobile swiping
				$modalCarousel.carousel('next');
			}
		}

		function challengeCard(uploads, challengeIndex) {
			const $card = $($('#challengeCard').html());
			$card.find('.challengeTitle').text(getChallengeName(challengeIndex));
			const $thumbnails = $card.find('.thumbnails');
			_.each(uploads, (upload) => {
				$thumbnails.append(thumbnail(upload));
			});
			return $card;
		}

		function emptyCard(text) {
			const $card = $($('#emptyCard').html());
			$card.find('.text').text(text);
			return $card;
		}

		function thumbnail(upload) {
			const url = getDownloadLinkForUpload(upload);
			const $thumbnail = $($('#thumbnail').html());
			if (isUploadWinner(upload)) $thumbnail.addClass('col-8');
			$thumbnail.find('.thumbnailLink').attr('file', upload.file);
			$thumbnail.find('.figure-img').prop('src', url);
			$thumbnail.find('.figure-caption').text(getTeamName(upload.teamIndex));
			$thumbnail.find('.thumbnailIcon').html(thumbnailIcon(upload));
			return $thumbnail;
		}

		function thumbnailIcon(upload) {
			if (isUploadWinner(upload)) {
				return $($('#winnerIcon').html());
			} else if (isUploadHonored(upload)) {
				return $($('#honoredIcon').html());
			}
			return null;
		}

		function carouselSlide(id, url, teamName, isActive, attrs) {
			const $slide = $($('#carouselSlide').html());
			_.each(attrs, (val, key) => {
				$slide.attr(key, val);
			});
			$slide.find('img').attr('src', url);
			$slide.find('.teamName').text(teamName);
			if (!!isActive) $slide.addClass('active');
			return $slide;
		}

		function scoreRow(team) {
			const objArr = [
				{className: 'team', ele: getTeamName(team.teamIndex)},
				{className: 'score', ele: "" + team.score}
			];
			return tr(objArr);
		}
	});
</script>
</body>
</html>
