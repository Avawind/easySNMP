easySNMP
========

A Symfony project created on September 27, 2018, 5:55 pm.
Créé par **Foissard Theo & Leclercq Corentin**.

Quelques informations :

**1. Projet Symfony 3.3.4**

Pour une meilleur compréhension de l'architecture MVC et des Bundles d'une application Symfony nous vous invitons à consulter les documentations sur le Framework **Symfony 3.3.4**.
 : https://symfony.com/what-is-symfony 


Les utilisateurs sont gérés par le Bundle **FOSUserBundle** : https://github.com/FriendsOfSymfony/FOSUserBundle

La communication avec le serveur de Queue est gérée par **OldSound\RabbitMqBundle** : https://github.com/php-amqplib/RabbitMqBundle


Le Bundle que nous avons rédigé est **USMB/SNMPBundle**, vous trouverez la majorité du code réalisé.
Le nom des dossiers le constituant sont exhaustifs. Les "modules" sont situés dans les dossier **Services**.

**2. Code & Commentaires**

Les commentaires sont rédigés en anglais.

Le pseudo **Avawind** correspond à **Théo Foissard** : https://github.com/Avawind


Les classes intéressantes :

- Le Controller 
- Tous les Services (Modules Config, Logging & Monitoring)
- Les Ressources (Configurations, Vues, Scripts JS)
- Les Objets dans le dossier Entity, attention certains ont été générés par le module de configuration.
- Dans le dossier **app/config/** : config.yml, routing.yml & security.yml

Le fonctionnement avec le serveur RabbitMq est intéressant, ainsi que la répartition de charge. Vous les trouverez dans le module Monitoring.
Les Scripts JavaScript de génération dynamique de contenu pour les vues sont également à voir.

 

**3. Architecture serveur**

Serveur web **Apache 2** avec **SSL**, accessible uniquement en **HTTPS**.
Certificat autosigné et CA locale pour le developpement.

Application **php5.6** avec le framework **Synfony V3.3.4**.

Base de Données : **MariaDB** (SQL).

Serveur de queue : **RabbitMQ 3.6.6, Erlang 19.2.1**.
