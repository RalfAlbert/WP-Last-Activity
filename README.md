WordPress Last Activity
=======================

Angeregt durch einen Beitrag[1] von Eric Teubert (http://satoripress.com) habe ich ein Plugin geschrieben
das den letzten Login eines Benutzers loggt und diesen auf der Benutzer-Seite als neue Spalte ausgibt.
Es wird sowol ein normaler Login als auch der Login via Cookie (stay-logged-in) geloggt.
Die Spalte mit dem letzten Login ist sortierbar, wodurch man recht schnell die aktivsten bzw. inaktivsten
Benutzer filtern kann.

[1] http://www.satoripress.com/2011/12/wordpress/plugin-development/find-users-by-last-login-activity-225/


Changelog
---------

v0.0 example.php https://gist.github.com/1512706
	Idee zum Plugin und erster Entwurf
	
v1.0 https://github.com/RalfAlbert/WP-Last-Activity
	Erste anwendbare Version des Plugins
	

Milestones
----------

v1.0.1

- Marker in Session(?) für User die via Cookie erfasst werden damit nicht bei jeden Seitenaufruf eine DB-Anfrage erzeugt wird.

v1.1

- Cronjobs um Benutzer nach x Tagen Inaktivität automatisch zu löschen
- Cronjob um Benutzer zu löschen die ihre Registrierung nach x Tagen noch nicht abgeschlossen haben
- Verschiedene Formate für die Anzeige der letzten Aktivität (vor x Tagen/Monate; zuletzt am xx.xx.xx; usw.)
- Farbige herviorhebung für Benutzer die über einen bestimmten Zeitraum inaktiv waren
- Backend für Einstellungen