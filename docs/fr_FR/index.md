RteSync
==========

Description
-----------

Plugin permettant de s'interfacer avec l'API RTE.

![RteSync icon](../images/RteSync_icon.png)

Installation
============

a. Installation

- Télécharger le plugin

- Activer le plugin
Pour cela il s'uffit d'aller dans la Gestion des plugin et de cliquer sur l'icône "RteSync" puis sur "Activer"
![Activer](../images/activer.png)

- Explication du cronHourly
La synchronisation tourne tous les jours à 1h pour récupérer les données jusqu'à J+3. Le Daily tourne à minuit et les informations J+3 ne sont pas encore disponibles sur l'API RTE.
![CronHourly](../images/cronHourly.png)

- Ajout d'un équipement pour activer une API RTE
Aller dans le menu Plugin -> Energie -> RteSync
![Menu](../images/menu.png)

Puis cliquer sur "Ajouter"
![Ajouter](../images/ajouter.png)

Choisissez un nom d'équipement et vous arriverez sur la page de configuration
![Configuration](../images/configuration.png)

Le plus important ici est d'indiquer le "ID Secret (base 64)"
Pour cela il faut se rendre sur le site [RTE Ecowatt](https://data.rte-france.com/catalog/-/api/consumption/Ecowatt/v4.0). De là il faudra créer un compte puis s'abonner à l'API en choisissant un Nom pour l'application (j'ai mis jeedom) ou rattacher l'API a une application déjà existante.
Ensuite se rendre dans [Mes applications](https://data.rte-france.com/group/guest/apps), cliquer sur l'application et récupérer le secret en cliquant sur "Copier en Base64" puis le mettre dans la configuration :
![Configuration](../images/configuration.png)

Après ça vous pouvez cliquer sur "Activer" et "Visible" puis "Sauvegarder" (et choisir l'objet parent si vous le souhaitez).

Ensuite vous pouvez vous rendre dans l'onglet "Commandes" et cliquer sur le bouton Tester correspondant à la commande "refresh". Les commandes manquantes vont se créer et le widget s'affichera dans le dashboard.
