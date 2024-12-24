<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Carte des Bassins de Vie et Intercommunalités</title>
	<!-- <script src="https://d3js.org/d3.v7.min.js"></script> -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/5.9.1/d3.min.js"></script>
	<link href="css/style.css" rel="stylesheet">
</head>

<body>
	<div class="haut">
		<h1>Carte des Bassins de Vie et Intercommunalités</h1>

		<!-- Menu déroulant de sélection des départements -->
		<div class="alignLabel">
			<label for="mySelect">1</label>
			<select id="mySelect">
				<option value="">-- Sélectionnez un département --</option>
				<option value="17">Charente-Maritime</option>
				<option value="33">Gironde</option>
				<option value="24">Dordogne</option>
				<option value="40">Landes</option>
				<option value="47">Lot-et-Garonne</option>
				<option value="64">Pyrénées-Atlantiques</option>
			</select>
		</div>
		<div class="alignLabel">
			<!-- Menu déroulant des bassins de vie (initialement caché) -->
			<label id="bassinSelectLabel" for="bassinSelect" style="display: none;">2</label>
			<select id="bassinSelect" style="display: none;"></select>
		</div>
		<legend id="legend" style="display: none;">Cliquer sur le nom de la CC pour afficher ses communes</legend>
	</div>
	<div class="posCarteListe">
		<div>
			<svg id="entete" viewBox="0 0 550 690" preserveAspectRatio="xMidYMid meet"></svg> <!-- Carte -->
		</div>
		<div class="liste">
			<ul id="intercoList" class="intercoListe"></ul> <!-- Liste des intercommunalités -->
		</div>
	</div>
	<script>
		document.getElementById('bassinSelect').addEventListener('change', function() {
			var bassinSelect = document.getElementById('bassinSelect');
			const legend = document.getElementById('legend');

			if (!bassinSelect.value || bassinSelect.style.display === 'none') {
				legend.style.display = 'none'; // Cacher la légende
				document.getElementById('entete').style.display = 'none';
			} else {
				legend.style.display = 'block'; // Afficher la légende
				document.getElementById('entete').style.display = 'block';
			}
		});


		const svg = document.getElementById("entete");

		function resizeSVG() {
			const width = window.innerWidth;
			const height = window.innerHeight;
			svg.setAttribute("width", width);
			svg.setAttribute("height", height);
		}

		window.addEventListener("resize", resizeSVG);
		resizeSVG(); // Appeler au chargement initial
	</script>
	<!-- <script src="https://d3js.org/d3.v7.min.js"></script> -->
	<script>
		// Sélectionner l'élément <select> du département
		const selectElement = document.getElementById('mySelect');

		// Ajouter un écouteur d'événement pour 'change'
		selectElement.addEventListener('change', function(event) {
			const selectedValue = event.target.value;

			legend.style.display = 'none'; // Cacher la légende au changement de dep
			// Si un département est sélectionné
			if (selectedValue) {
				clearPreviousData(); // Réinitialisation
				entete(selectedValue); // Recharger la carte et les données
				document.getElementById('bassinSelect').style.display = 'inline'; // Afficher le menu des bassins
				document.getElementById('bassinSelectLabel').style.display = 'inline-block'; // Afficher le menu des bassins
				document.getElementById('entete').style.display = 'none';

			} else {
				// Masquer la carte, la liste et les autres éléments si aucun département n'est sélectionné
				clearPreviousData();
				document.getElementById('bassinSelect').style.display = 'none'; // Cacher le menu des bassins
				document.getElementById('bassinSelectLabel').style.display = 'none'; // Cacher le menu des bassins

			}
		});

		// Fonction pour réinitialiser les données affichées
		function clearPreviousData() {
			d3.select('#bassinSelect').html(''); // Réinitialise le menu déroulant des bassins de vie
			d3.select('#intercoList').html(''); // Réinitialise la liste des intercommunalités
			d3.select('svg#entete').selectAll('*').remove(); // Réinitialise la carte
		}

		// Fonction principale pour dessiner la carte et gérer les interactions
		function entete(depNum) {
			// Vérifier si le département est sélectionné et si les paramètres de projection sont définis
			if (!depNum) {
				console.error('Aucun département sélectionné.');
				return;
			}

			// Paramètres de projection explicites pour chaque département
			let params;
			switch (depNum) {
				case "17":
					params = {
						center: [-0.701828578654626, 45.747218813331976],
						scale: 19500,
						translate: [290, 350]
					};
					break;
				case "24":
					params = {
						center: [0.7572205, 45.1469486],
						scale: 20600,
						translate: [280, 355]
					};
					break;
				case "33":
					params = {
						center: [-0.612943, 44.827778],
						scale: 18900,
						translate: [220, 380]
					};
					break;
				case "40":
					params = {
						center: [-0.612943, 44.827778],
						scale: 19300,
						translate: [300, -110]
					};
					break;
				case "47":
					params = {
						center: [0.4502368, 44.2470173],
						scale: 25600,
						translate: [275, 355]
					};
					break;
				case "64":
					params = {
						center: [-0.7532809, 43.3269942],
						scale: 17600,
						translate: [310, 210]
					};
					break;
					// Ajouter des cas pour d'autres départements ici
				default:
					console.error('Les paramètres de projection pour ce département ne sont pas définis.');
					return;
			}

			// Charger les données GeoJSON et CSV
			var promises = [
				d3.json('js/maps/departements/communes-' + depNum + '.geojson'),
				d3.csv('datas/DatasCommunes.csv')
			];

			Promise.all(promises).then(function(value) {
				var map = value[0];
				var data = value[1];

				// Filtrer les données pour le département sélectionné
				const filteredData = data.filter(d => d.CodeDept == depNum);

				// Si aucune donnée, masquer la carte
				if (filteredData.length === 0) {
					clearPreviousData();
					return;
				}

				// Créer une échelle de couleur basée sur les couleurs des intercommunalités
				var colorScale = d3.scaleQuantile()
					.domain([1, 2, 3, 4, 5, 6])
					.range(['#adc178', '#a8b2c1', '#b5e2fa', '#f28482', '#f6bd60', '#d3cdad']);


				// Associer les informations des communes avec les données
				map.features.forEach(d => {
					var result = filteredData.filter(dep => d.properties.insee == dep.CodeInsee);
					if (result[0]) {
						d.properties.BassinDeVie = result[0].BassinDeVie;
						d.properties.Couleur = result[0].CouleurInterco;
						d.properties.NomComm = result[0].NomComm;
						d.properties.NomInterco = result[0].NomInterco;
						d.properties.EditionPrint = result[0].EditionPrint;
					}
				});

				// Créer le dropdown des bassins de vie
				createDropdown(filteredData, colorScale);
				// Dessiner la carte avec les données
				parseData(map, filteredData, colorScale, params);
			});

			// Fonction pour créer le dropdown des bassins de vie
			function createDropdown(data, colorScale) {
				const uniqueBassins = [...new Set(data.map(d => d.BassinDeVie))];
				const dropdown = d3.select('#bassinSelect');
				dropdown.append('option').attr('value', '').text('-- Sélectionnez un bassin de vie --');

				uniqueBassins.forEach(bassin => {
					dropdown.append('option').attr('value', bassin).text(bassin);
				});

				dropdown.on('change', function() {
					const selectedBassin = this.value;
					updateMap(selectedBassin, data, colorScale);
					displayIntercoLists(selectedBassin, data, colorScale);


				});
			}

			// Mettre à jour la carte en fonction du bassin de vie sélectionné
			function updateMap(selectedBassin, data, colorScale) {
				d3.selectAll('.commune')
					.style('opacity', function(d) {
						return selectedBassin && d.properties.BassinDeVie === selectedBassin ? 1 : 0.2;
					})
					.style('fill', function(d) {
						return colorScale(d.properties.Couleur) || '#ccc'; // Appliquer la couleur de l'intercommunalité
					});
			}

			// Afficher les intercommunalités associées au bassin sélectionné
			function displayIntercoLists(selectedBassin, data, colorScale) {
				const intercos = [...new Set(data.filter(d => d.BassinDeVie === selectedBassin).map(d => d.NomInterco))];

				const list = d3.select('#intercoList');
				list.html('');
				list.style('list-style-type', 'none'); // Retirer les puces de la liste

				intercos.sort().forEach(interco => {
					var li = list.append('li')
						.attr('class', 'intercoItem');

					// Chercher la couleur de l'intercommunalité correspondante
					const intercoData = data.find(d => d.NomInterco === interco);
					const intercoColor = colorScale(intercoData ? intercoData.CouleurInterco : 0) || '#ccc';

					li.append('span')
						.text(interco)
						.style('color', 'black')
						.style('background-color', intercoColor)
						.style('padding', '5px 10px')
						.style('display', 'inline-block')
						.style('border-radius', '3px');

					li.on('mouseover', function() {
						highlightCirco(interco, colorScale);
					});

					li.on('mouseout', function() {
						resetHighlight(selectedBassin, colorScale);
					});

					li.on('click', function() {
						displayCommunes(interco, data, li, colorScale);
					});
				});
			}

			// Afficher les communes d'une intercommunalité
			function displayCommunes(interco, data, intercoElement, colorScale) {
				const communes = data.filter(d => d.NomInterco === interco);
				communes.sort((a, b) => a.NomComm.localeCompare(b.NomComm));

				const intercoData = data.find(d => d.NomInterco === interco);
				const intercoColor = colorScale(intercoData ? intercoData.CouleurInterco : 0) || '#ccc';

				let communesList = intercoElement.select('.communesList');
				if (communesList.empty()) {
					communesList = intercoElement.append('ul').attr('class', 'communesList');
					communes.forEach(function(commune) {
						communesList.append('li')
							.style('background-color', `${intercoColor}40`)
							.style('color', 'black')
							.html(commune.NomComm + ' <b>(' + commune.EditionPrint + ')</b>') // Mise en gras de la partie EditionPrint
							// .text(commune.NomComm + ' (' + commune.EditionPrint + ')')
							.on('mouseover', function() {
								d3.select(this).style('background-color', intercoColor); // 100% d'opacité
								highlightCommune(commune.NomComm, colorScale); // Mettre en surbrillance la commune sur la carte

							})
							.on('mouseout', function() {
								d3.select(this).style('background-color', `${intercoColor}40`); // 25% de transparence
								resetCommuneHighlight(colorScale); // Réinitialiser la mise en surbrillance sur la carte
							});
					});
				} else {
					communesList.remove();
				}
			}

			// Fonction pour surligner une commune sur la carte
			function highlightCommune(communeNom, colorScale) {
				d3.selectAll('.commune')
					.style('opacity', 1)
					.style('fill', function(d) {
						return d.properties.NomComm === communeNom ? 'black' : colorScale(d.properties.Couleur) || '#ccc';
					})
					.style('stroke', function(d) {
						return d.properties.NomComm === communeNom ? 'black' : null;
					})
					.style('stroke-width', function(d) {
						return d.properties.NomComm === communeNom ? '2px' : null;
					});
			}

			// Réinitialiser la mise en surbrillance d'une commune
			function resetCommuneHighlight(colorScale) {
				d3.selectAll('.commune')
					.style('fill', function(d) {
						return colorScale(d.properties.Couleur) || '#ccc';
					})
					.style('stroke', null)
					.style('stroke-width', null);
			}

			// Surligner les communes d'une intercommunalité
			function highlightCirco(interco, colorScale) {
				d3.selectAll('.commune')
					.style('opacity', function(d) {
						return d.properties.NomInterco === interco ? 1 : 0.2;
					})
					.style('stroke', function(d) {
						return d.properties.NomInterco === interco ? '#fff' : null;
					})
					.style('stroke-width', function(d) {
						return d.properties.NomInterco === interco ? '1px' : null;
					});
			}

			// Réinitialiser la mise en surbrillance des intercommunalités
			function resetHighlight(selectedBassin, colorScale) {
				d3.selectAll('.commune')
					.style('opacity', function(d) {
						return d.properties.BassinDeVie === selectedBassin ? 1 : 0.2;
					})
					.style('fill', function(d) {
						return colorScale(d.properties.Couleur) || '#ccc';
					})
					.style('stroke', null)
					.style('stroke-width', null);
			}

			// Fonction pour dessiner la carte
			function parseData(map, data, colorScale, params) {
				if (!params || !params.center || !params.scale || !params.translate) {
					console.error('Les paramètres de projection sont manquants ou incorrects.');
					return;
				}

				var projection = d3.geoMercator()
					.center(params.center) // Définir le centre
					.scale(params.scale) // Définir l'échelle
					.translate(params.translate); // Définir la translation

				var geoPath = d3.geoPath().projection(projection);
				var svg = d3.select("svg#entete"),
					width = +svg.attr("width"),
					height = +svg.attr("height");

				// TOOL TIPS
				const div = d3
					.select('body')
					.append('div')
					.attr('id', 'tooltip')
					.attr('class', 'tooltip')

					.style('opacity', 0);

				var carte = svg.append('g')
					.attr('class', 'carte')
					.selectAll('path')
					.data(map.features)
					.enter()
					.append('path')
					.attr('d', geoPath)
					.attr('class', 'commune')
					.style('fill', function(d) {
						return colorScale(d.properties.Couleur) || '#ccc';
					})
					.style('stroke', '#000')
					.style('stroke-width', '0.5px')
					.style('cursor', 'pointer')
					.style('opacity', 0.2)

					.on('mouseover', (d, i, nodes) => {
						d3.select(nodes[i]).classed('visu2', true)
							.style('fill', function(d) {
								return '#000';
							})
						div
							.transition()
							.duration(200)
							.style('opacity', 1);
						// .style('background-color', colorScale(d.properties.Couleur) || '#ccc');

						div
							.html('<span class="tooltiptitre" style="background-color: ' +
								(colorScale(d.properties.Couleur) || '#ccc') + ';">' +
								d.properties.NomComm + ' <b>(' + d.properties.EditionPrint + ')</b></span><br/>' +
								'<span class="tooltiptssitre">' + d.properties.BassinDeVie + '</span><br/>' +
								'<span class="tooltiptssitre">' + d.properties.NomInterco + '</span>')
							.style('left', d3.event.pageX + 10 + 'px')
							.style('top', d3.event.pageY + 'px');
					})

					.on('mouseout', (d, i, nodes) => {
						d3.select(nodes[i]).classed('visu2', false)
							.style('fill', function(d) {
								return colorScale(d.properties.Couleur) || '#ccc';
							})
						div
							.transition()
							.duration(500)
							.style('opacity', 0);
					})
			}
		}
	</script>

</body>

</html>