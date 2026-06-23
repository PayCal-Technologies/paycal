<?php
/**
 * Localized Transparency article renderer for Redis connections reconciliation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$lang = isset($articleLanguage) && is_string($articleLanguage) ? $articleLanguage : 'en';

$copy = [
  'de' => [
    'title' => 'Wie PayCal Geschäftsverbindungen korrekt und auditierbar hält',
    'current' => 'Integrität von Geschäftsverbindungen',
    'meta' => 'Wie PayCal Lebenszyklus-Datensätze von Geschäftsverbindungen von Redis-Lookup-Indizes trennt, abgeleitete Indexabweichungen auditiert und aktive Mitgliedschaft, ausstehenden Zugriff und Eigentümerautorität sauber hält.',
    'deck' => 'Geschäftsverbindungen sind mehr als eine Personenliste. Im Juni 2026 haben wir geschärft, wie PayCal Business Verbindungen, Mitgliedschaften, Zugriffsanfragen, Einladungen und aktiven Zugriff in Redis darstellt. Dieser Artikel erklärt das Modell, die gefundene Abweichung, den Reparaturbefehl und warum die Änderung für Datenschutz, Dashboard-Korrektheit und künftige Auditierbarkeit wichtig ist.',
    'summary' => 'Zusammenfassung',
    'problem' => 'Das Problem',
    'model' => 'Das Modell, das wir jetzt durchsetzen',
    'drift' => 'Beispiel für Redis-Abweichung',
    'found' => 'Was wir vor der Reparatur gefunden haben',
    'repair' => 'Der Reparaturbefehl, den wir gebaut haben',
    'operators' => 'Beispiele für künftige Betreiber',
    'exit' => 'CI/CD- und Ops-Exit-Codes',
    'actions' => 'Unsere Maßnahmen',
    'outcome' => 'Endergebnis',
    'why' => 'Warum das wichtig ist',
    'not' => 'Was dies nicht war',
    'future' => 'Künftiges Betriebsverfahren',
    'summary_rows' => [
      ['Betroffener Bereich', 'PayCal-Business-Verbindungslebenszyklen und Redis-Lookup-Indizes'],
      ['Problem', 'Ältere abgeleitete Redis-Sets konnten aktive Mitgliedschaft, ausstehenden Workflow-Status und veraltete historische Lookup-Einträge vermischen'],
      ['Kernkorrektur', 'Verbindungshashes bleiben kanonisch; aktive Mitglieder, ausstehende Verbindungen und nicht-terminale Lookup-Indizes sind getrennt'],
      ['Betriebstool', '<code>scripts/paycal business:connections:audit</code> auditiert und repariert abgeleitete Indexabweichungen'],
      ['Fund vor der Reparatur', '18.349 bekannte reparierbare Abweichungen, null Eigentümerverletzungen, null unbekannte Abweichungen'],
      ['Endzustand nach der Reparatur', '0 Abweichungen, 0 Eigentümerverletzungen, 0 unbekannte Abweichungen'],
    ],
    'problem_p' => [
      'PayCal Business nutzt Redis für schnelle Verbindungs-Lookups. Der kanonische Datensatz ist ein Hash wie <code>business:connection:{businessId}:{userUUID}</code>. Dieser Hash speichert den Lebenszyklus einer Verbindung: Rolle, Status, Scopes, Zeitstempel, Einladungs- oder Anfragemetadaten und zugehörige Audit-Felder.',
      'Das Problem lag nicht im kanonischen Hash. Das Problem war semantische Abweichung in abgeleiteten Redis-Sets, die als Lookup-Indizes genutzt wurden. Historisch wurden <code>business:members:{businessId}</code> und <code>business:user:{userUUID}</code> manchmal als breite Verbindungsindizes verwendet. Dadurch konnte älterer Code eine ausstehende oder veraltete Verbindung zu leicht wie aktive Mitgliedschaft behandeln.',
      'Diese Unterscheidung ist wichtig. Eine ausstehende Zugriffsanfrage ist keine aktive Mitgliedschaft. Eine akzeptierte Verbindung ist nicht automatisch verschlüsselter Datenzugriff, solange erforderliche Zustimmung und Key-Wrap-Status nicht gültig sind. Ein abgeleitetes Redis-Set ist keine Autorität; es ist ein austauschbarer Index.',
    ],
    'model_intro' => 'Das gehärtete Modell trennt Lebenszyklusstatus von aktivem Zugriffsstatus:',
    'model_after' => 'Damit hat jedes Redis-Set genau eine Bedeutung. Dashboard-Zählungen können sich auf aktive Mitgliederindizes verlassen. Ausstehende Warteschlangen können <code>business:pending:{businessId}</code> verwenden. Verbindungsbewusste UI kann Lookup-Indizes nutzen, ohne aktiven Zugriff zu gewähren.',
    'drift_intro' => 'Ein veralteter Indexeintrag sieht so aus:',
    'drift_second' => 'Ein weiteres reparierbares Beispiel ist eine aktive Verbindung, der aktive Indizes fehlen:',
    'drift_after' => 'In beiden Fällen erfindet die Reparatur keine Verbindung. Sie gleicht nur abgeleitete Sets mit dem kanonischen Verbindungshash ab.',
    'found_intro' => 'Das reine Reporting-Audit fand viele abgeleitete Indexabweichungen, aber keine Autoritätskorruption und keine terminalen Zustände in Live-Indizes.',
    'found_conclusion' => 'Die Schlussfolgerung war wichtig: Das Audit fand Migrationsreste in abgeleiteten Indizes, ohne Hinweise auf terminale Zustandslecks, Eigentümerrollen-Korruption oder unbekannte Abweichungen. Der kanonische Lebenszyklusgraph war intakt, und die Eigentümerautorität war intakt.',
    'repair_p' => ['Wir erstellten <code>scripts/connections-audit.php</code> und machten es über den bestehenden internen Befehls-Dispatcher verfügbar:', 'Der Befehl hat zwei Modi:', 'Der Befehl wählt niemals automatisch Eigentümer aus, schreibt keine Rollenautorität um und erstellt keine kanonischen Verbindungen. Er gleicht nur austauschbare Indizes mit kanonischen Hashes ab.'],
    'modes' => ['<strong>Nur Bericht:</strong> scannt Verbindungshashes und meldet Abweichungen ohne Redis zu verändern.', '<strong>Reparatur:</strong> repariert bekannte abgeleitete Indexabweichungen durch Hinzufügen oder Entfernen von Redis-Set-Mitgliedern.'],
    'operator_labels' => ['Vollständiges Nur-Bericht-Audit ausführen', 'Bericht für ein einzelnes Geschäft ausführen', 'Berichtsartefakt vor der Reparatur speichern', 'Bekannte Abweichung reparieren', 'Bericht erneut ausführen und sauberes Ergebnis erwarten', 'Erwartete gesunde Zusammenfassung nach der Reparatur'],
    'exit_intro' => 'Wir machten den Befehl maschinenlesbar, damit CI- und Betriebsjobs normale reparierbare Abweichung von unsicheren Zuständen unterscheiden können.',
    'exit_after' => 'Dadurch eignet sich das Tool für geplante Prüfungen und Deployment-Gates. Ein Nur-Bericht-Job kann mit Code <code>1</code> fehlschlagen, wenn bekannte Abweichung existiert, während ein Reparaturjob nach erfolgreicher Reparatur bekannter Kategorien <code>0</code> zurückgeben kann.',
    'actions_l' => ['Aktive Mitgliedschaftsindizes von Verbindungslebenszyklus-Indizes getrennt.', '<code>business:connections:{businessId}</code> und <code>business:connections:user:{userUUID}</code> für nicht-terminalen Lookup hinzugefügt.', '<code>business:pending:{businessId}</code> für ausstehende Workflow-Sichtbarkeit hinzugefügt.', 'Den zentralen Verbindungsschreiber aktualisiert, sodass alle abgeleiteten Sets aus einem Pfad gepflegt werden.', 'Terminale Übergänge gehärtet, sodass widerrufene, abgelehnte, abgelaufene und zurückgezogene Verbindungen nicht direkt aktiv werden können.', 'Scope-Preset- und Policy-Version-Felder zu Verbindungsschreibvorgängen hinzugefügt.', 'Seed- und Konsolidierungsskripte aktualisiert, damit sie die neuen Indizes konsistent füllen.', 'Reconciliation-Befehl mit Bericht, Reparatur, JSON, Bucket-Zählungen und CI-freundlichen Exit-Codes gebaut.', 'Nur-Bericht ausgeführt, bekannte Abweichung repariert, Bericht erneut ausgeführt und sauberen Endzustand verifiziert.', 'Gezielte PHPUnit-Verbindungs-/Cache-/Site-Suite nach der Reparatur ausgeführt.'],
    'outcome_p' => ['Die finale Reparatursequenz ergab dieses Ergebnis:', 'Die gezielte Testsuite bestand nach der Reparatur:', 'Nach den gezielten Tests führten wir einen zusätzlichen Reconciliation-Lauf aus, um zu bestätigen, dass durch Fixtures erstellte Indizes sauber waren. Das finale Audit ergab:'],
    'why_p' => ['Payroll-Zusammenarbeit ist sicherheitssensibel. Dashboards, Einladungen, Zugriffsgenehmigungen, Verschlüsselungszustimmung und Widerruf hängen alle von sauberen Verbindungssemantiken ab.', 'Das neue Modell gibt PayCal eine stärkere operative Grenze:', 'Diese Trennung hält Dashboard-Mitgliederzahlen korrekt, verhindert, dass ausstehender Workflow-Status als aktiver Zugriff behandelt wird, hält terminale Zustände aus Live-Indizes heraus und gibt Betreibern eine sichere Möglichkeit, Redis-Abweichungen ohne Änderung von Autoritätsdatensätzen zu reparieren.'],
    'not_l' => ['Es war kein Hinweis auf Korruption der Eigentümerrolle. Das Audit fand null Eigentümerverletzungen.', 'Es war kein Hinweis darauf, dass widerrufene oder abgelehnte Nutzer in Live-Mitgliederindizes auftauchten. Terminal-Leakage-Buckets waren null.', 'Es war keine Umschreibung kanonischer Verbindungsdatensätze. Die Reparatur glich nur abgeleitete Redis-Sets ab.', 'Es war keine Änderung der öffentlichen Geschäftssuchrichtlinie. Öffentliche Suche bleibt durch Sichtbarkeit, Abonnementstatus, Freigabestatus und Listing-Status geregelt.'],
    'future_l' => ['Vor großen Reparaturen einen Redis-Snapshot oder ein Backup erstellen.', 'Nur-Bericht ausführen und das JSON-Artefakt speichern.', '<code>--fix</code> zuerst in einer Nicht-Produktionsumgebung ausführen.', 'Nur-Bericht erneut ausführen und <code>drift=0</code>, <code>owner_violations=0</code> und <code>other=0</code> bestätigen.', 'Gezielte Verbindungs- und Business-Workflow-Tests ausführen.', 'Dashboard-Zählungen, ausstehende Anfragen, Einladungsannahme, Zugriffsgenehmigung, Widerruf und öffentliche Suche per Smoke-Test prüfen.', 'Produktionsreparatur nur in einem ruhigen Fenster ausführen, nachdem der Nicht-Produktionslauf unauffällig war.'],
  ],
];

$copy['es'] = [
  'title' => 'Cómo PayCal mantiene las conexiones de negocio precisas y auditables',
  'current' => 'Integridad de conexiones de negocio',
  'meta' => 'Cómo PayCal separa los registros de ciclo de vida de conexiones de negocio de los índices de búsqueda en Redis, audita la deriva de índices derivados y mantiene limpias la membresía activa, el acceso pendiente y la autoridad de propietarios.',
  'deck' => 'Las conexiones de negocio son más que una lista de personas. En junio de 2026 reforzamos cómo PayCal Business representa conexiones, solicitudes de acceso, invitaciones y acceso activo en Redis. Este artículo explica el modelo, la deriva que encontramos, el comando de reparación y por qué el cambio importa para privacidad, exactitud de paneles y auditoría futura.',
] + $copy['de'];
$copy['fr'] = [
  'title' => 'Comment PayCal garde les connexions Business exactes et auditables',
  'current' => 'Intégrité des connexions Business',
  'meta' => 'Comment PayCal sépare les enregistrements de cycle de vie des connexions Business des index Redis, audite la dérive des index dérivés et garde propres l’adhésion active, l’accès en attente et l’autorité propriétaire.',
  'deck' => 'Les connexions Business sont plus qu’une liste de personnes. En juin 2026, nous avons renforcé la façon dont PayCal Business représente les connexions, adhésions, demandes d’accès, invitations et accès actifs dans Redis. Cet article explique le modèle, la dérive trouvée, la commande de réparation et l’importance de ce changement pour la confidentialité, les tableaux de bord et l’auditabilité.',
] + $copy['de'];
$copy['it'] = [
  'title' => 'Come PayCal mantiene accurate e verificabili le connessioni Business',
  'current' => 'Integrità delle connessioni Business',
  'meta' => 'Come PayCal separa i record del ciclo di vita delle connessioni Business dagli indici Redis, verifica la deriva degli indici derivati e mantiene puliti appartenenza attiva, accesso in sospeso e autorità del proprietario.',
  'deck' => 'Le connessioni Business sono più di un elenco di persone. Nel giugno 2026 abbiamo rafforzato il modo in cui PayCal Business rappresenta connessioni, appartenenze, richieste di accesso, inviti e accesso attivo in Redis. Questo articolo spiega il modello, la deriva trovata, il comando di riparazione e perché il cambiamento conta per privacy, dashboard e audit futuri.',
] + $copy['de'];
$copy['nl'] = [
  'title' => 'Hoe PayCal bedrijfsverbindingen nauwkeurig en controleerbaar houdt',
  'current' => 'Integriteit van bedrijfsverbindingen',
  'meta' => 'Hoe PayCal levenscyclusrecords van bedrijfsverbindingen scheidt van Redis-lookupindexen, afgeleide indexdrift controleert en actief lidmaatschap, wachtende toegang en eigenaarautoriteit schoon houdt.',
  'deck' => 'Bedrijfsverbindingen zijn meer dan een lijst mensen. In juni 2026 hebben we aangescherpt hoe PayCal Business verbindingen, lidmaatschappen, toegangsaanvragen, uitnodigingen en actieve toegang in Redis voorstelt. Dit artikel legt het model uit, de drift die we vonden, de reparatieopdracht en waarom dit telt voor privacy, dashboards en auditbaarheid.',
] + $copy['de'];
$copy['pt'] = [
  'title' => 'Como o PayCal mantém conexões de negócios precisas e auditáveis',
  'current' => 'Integridade de conexões de negócios',
  'meta' => 'Como o PayCal separa registros canônicos de ciclo de vida de conexões de negócios dos índices Redis, audita deriva de índices derivados e mantém limpos associação ativa, acesso pendente e autoridade de proprietário.',
  'deck' => 'Conexões de negócio são mais do que uma lista de pessoas. Em junho de 2026 reforçamos como o PayCal Business representa conexões, associações, solicitações de acesso, convites e acesso ativo no Redis. Este artigo explica o modelo, a deriva encontrada, o comando de reparo e por que isso importa para privacidade, dashboards e auditoria futura.',
] + $copy['de'];
$copy['tl'] = [
  'title' => 'Paano pinananatiling tama at naa-audit ng PayCal ang Business connections',
  'current' => 'Integridad ng Business connections',
  'meta' => 'Kung paano hinihiwalay ng PayCal ang lifecycle records ng business connection mula sa Redis lookup indexes, ina-audit ang derived-index drift, at pinananatiling malinis ang active membership, pending access, at owner authority.',
  'deck' => 'Ang business connections ay higit pa sa listahan ng mga tao. Noong Hunyo 2026, pinatibay namin kung paano kinakatawan ng PayCal Business ang connections, membership state, access requests, invites, at active access sa Redis. Ipinapaliwanag ng artikulong ito ang modelo, ang drift na nakita namin, ang repair command, at kung bakit mahalaga ito sa privacy, dashboard correctness, at future auditability.',
] + $copy['de'];
$copy['tr'] = [
  'title' => 'PayCal işletme bağlantılarını nasıl doğru ve denetlenebilir tutar',
  'current' => 'İşletme bağlantıları bütünlüğü',
  'meta' => 'PayCal’ın işletme bağlantı yaşam döngüsü kayıtlarını Redis arama indekslerinden nasıl ayırdığı, türetilmiş indeks sapmasını nasıl denetlediği ve aktif üyelik, bekleyen erişim ile sahip yetkisini nasıl temiz tuttuğu.',
  'deck' => 'İşletme bağlantıları bir kişi listesinden fazlasıdır. Haziran 2026’da PayCal Business’ın bağlantıları, üyelik durumunu, erişim isteklerini, davetleri ve aktif erişimi Redis’te nasıl temsil ettiğini sıkılaştırdık. Bu makale modeli, bulduğumuz sapmayı, onarım komutunu ve değişikliğin gizlilik, pano doğruluğu ve gelecekteki denetlenebilirlik için neden önemli olduğunu açıklar.',
] + $copy['de'];
$copy['hi'] = [
  'title' => 'PayCal Business connections को सटीक और ऑडिट योग्य कैसे रखता है',
  'current' => 'Business connections अखंडता',
  'meta' => 'PayCal कैसे business connection lifecycle records को Redis lookup indexes से अलग करता है, derived-index drift का audit करता है, और active membership, pending access तथा owner authority को साफ रखता है।',
  'deck' => 'Business connections केवल लोगों की सूची नहीं हैं। जून 2026 में हमने मजबूत किया कि PayCal Business Redis में connections, membership state, access requests, invites और active access को कैसे दर्शाता है। यह लेख मॉडल, मिली drift, repair command और privacy, dashboard correctness तथा future auditability के लिए इसके महत्व को समझाता है।',
] + $copy['de'];

$localizedOverrides = [
  'es' => [
    'summary' => 'Resumen ejecutivo',
    'problem' => 'El problema',
    'model' => 'El modelo que ahora aplicamos',
    'drift' => 'Ejemplo de deriva en Redis',
    'found' => 'Qué encontramos antes de reparar',
    'repair' => 'El comando de reparación que construimos',
    'operators' => 'Ejemplos para futuros operadores',
    'exit' => 'Códigos de salida para CI/CD y operaciones',
    'actions' => 'Acciones que tomamos',
    'outcome' => 'Resultado final',
    'why' => 'Por qué importa',
    'not' => 'Lo que esto no fue',
    'future' => 'Procedimiento operativo futuro',
    'summary_rows' => [
      ['Área afectada', 'Registros de ciclo de vida de conexiones de PayCal Business e índices de búsqueda en Redis'],
      ['Problema', 'Conjuntos derivados antiguos de Redis podían mezclar membresía activa, estado pendiente de flujo de trabajo y entradas históricas obsoletas'],
      ['Corrección principal', 'Los hashes de conexión siguen siendo canónicos; miembros activos, conexiones pendientes e índices no terminales están separados'],
      ['Herramienta operativa', '<code>scripts/paycal business:connections:audit</code> audita y repara deriva de índices derivados'],
      ['Hallazgo antes de reparar', '18.349 hallazgos reparables conocidos, cero violaciones de propietario, cero deriva desconocida'],
      ['Estado final después de reparar', '0 deriva, 0 violaciones de propietario, 0 deriva desconocida'],
    ],
    'problem_p' => [
      'PayCal Business usa Redis para búsquedas rápidas de conexiones. El registro canónico es un hash como <code>business:connection:{businessId}:{userUUID}</code>. Ese hash guarda el ciclo de vida de una conexión: rol, estado, alcances, marcas de tiempo, metadatos de invitación o solicitud y campos de auditoría.',
      'El problema no era el hash canónico. El problema era la deriva semántica en conjuntos derivados de Redis usados como índices de búsqueda. Históricamente, <code>business:members:{businessId}</code> y <code>business:user:{userUUID}</code> a veces se usaban como índices amplios de conexión. Eso hacía demasiado fácil que código antiguo tratara una conexión pendiente u obsoleta como membresía activa.',
      'La distinción importa. Una solicitud de acceso pendiente no es membresía activa. Una conexión aceptada no es automáticamente acceso a datos cifrados si el consentimiento y el estado de key-wrap requeridos no son válidos. Un conjunto derivado de Redis no es autoridad; es un índice reemplazable.',
    ],
    'model_intro' => 'El modelo endurecido separa el estado del ciclo de vida del estado de acceso activo:',
    'model_after' => 'Así cada conjunto Redis tiene un solo significado. Los conteos del panel pueden usar índices de miembros activos. Las colas pendientes pueden usar <code>business:pending:{businessId}</code>. La interfaz puede usar índices de conexión sin conceder acceso activo.',
    'drift_intro' => 'Una entrada de índice obsoleta se ve así:',
    'drift_second' => 'Otro ejemplo reparable es una conexión activa a la que le faltan índices activos:',
    'drift_after' => 'En ambos casos, la reparación no inventa una conexión. Solo reconcilia conjuntos derivados contra el hash canónico de conexión.',
    'found_intro' => 'La auditoría en modo informe encontró mucha deriva de índices derivados, pero ninguna corrupción de autoridad ni estados terminales en índices vivos.',
    'found_conclusion' => 'La conclusión fue importante: la auditoría encontró residuos de migración en índices derivados, sin evidencia de fuga de estados terminales, corrupción de roles de propietario ni deriva desconocida. El grafo canónico de ciclo de vida estaba intacto, y la autoridad de propietario también.',
    'repair_p' => ['Creamos <code>scripts/connections-audit.php</code> y lo expusimos mediante el despachador interno existente:', 'El comando tiene dos modos:', 'El comando nunca selecciona propietarios automáticamente, nunca reescribe autoridad de roles y nunca crea conexiones canónicas. Solo reconcilia índices desechables contra hashes canónicos.'],
    'modes' => ['<strong>Solo informe:</strong> escanea hashes de conexión y reporta deriva sin mutar Redis.', '<strong>Reparación:</strong> corrige deriva conocida agregando o quitando miembros de conjuntos Redis.'],
    'operator_labels' => ['Ejecutar auditoría completa solo informe', 'Ejecutar informe para un negocio', 'Guardar artefacto antes de reparar', 'Reparar deriva conocida', 'Repetir informe y esperar resultado limpio', 'Resumen saludable esperado tras reparar'],
    'exit_intro' => 'Hicimos el comando legible por máquinas para que CI y operaciones distingan deriva reparable normal de condiciones inseguras.',
    'exit_after' => 'Esto permite usar la herramienta en comprobaciones programadas y gates de despliegue. Un job solo informe puede fallar con código <code>1</code> cuando hay deriva conocida, mientras que un job de reparación puede devolver <code>0</code> después de reparar categorías conocidas.',
    'actions_l' => ['Separamos índices de membresía activa de índices de ciclo de vida de conexión.', 'Agregamos <code>business:connections:{businessId}</code> y <code>business:connections:user:{userUUID}</code> para búsqueda no terminal.', 'Agregamos <code>business:pending:{businessId}</code> para visibilidad de flujos pendientes.', 'Actualizamos el escritor central de conexiones para mantener todos los conjuntos derivados desde una sola ruta.', 'Endurecimos transiciones terminales para que conexiones revocadas, rechazadas, vencidas o retiradas no pasen directo a activas.', 'Agregamos campos de preset de alcance y versión de política.', 'Actualizamos scripts de semillas y consolidación.', 'Construimos el comando con informe, reparación, JSON, buckets y códigos de salida para CI.', 'Ejecutamos informe, reparación, nuevo informe y verificamos estado limpio.', 'Ejecutamos la suite PHPUnit enfocada en conexiones/cache/sites.'],
    'outcome_p' => ['La secuencia final de reparación produjo este resultado:', 'La suite de pruebas enfocada pasó después de la reparación:', 'Después de las pruebas enfocadas, ejecutamos una pasada adicional para confirmar que los índices creados por fixtures estaban limpios. La auditoría final devolvió:'],
    'why_p' => ['La colaboración de nómina es sensible a seguridad. Paneles, invitaciones, aprobaciones, consentimiento de cifrado y revocación dependen de semántica limpia de conexiones.', 'El nuevo modelo da a PayCal un límite operativo más fuerte:', 'Esa separación mantiene correctos los conteos de miembros, impide tratar pendientes como acceso activo, mantiene estados terminales fuera de índices vivos y da a operadores una forma segura de reparar Redis sin mutar autoridad.'],
    'not_l' => ['No fue evidencia de corrupción de rol propietario. La auditoría encontró cero violaciones.', 'No fue evidencia de usuarios revocados o rechazados filtrados a índices vivos. Los buckets de fuga terminal fueron cero.', 'No fue una reescritura de registros canónicos. La reparación solo reconcilió conjuntos Redis derivados.', 'No fue un cambio de política de búsqueda pública. La búsqueda pública sigue gobernada por visibilidad, suscripción, aprobación y estado de listado.'],
    'future_l' => ['Tomar snapshot o backup de Redis antes de reparaciones grandes.', 'Ejecutar solo informe y guardar el artefacto JSON.', 'Ejecutar <code>--fix</code> primero en un entorno no productivo.', 'Repetir solo informe y confirmar <code>drift=0</code>, <code>owner_violations=0</code> y <code>other=0</code>.', 'Ejecutar pruebas enfocadas de conexiones y workflows Business.', 'Hacer smoke test de conteos, solicitudes pendientes, aceptación de invitación, aprobación, revocación y búsqueda pública.', 'Reparar producción solo en una ventana tranquila después de una ejecución no productiva sin sorpresas.'],
  ],
  'fr' => [
    'summary' => 'Résumé exécutif', 'problem' => 'Le problème', 'model' => 'Le modèle désormais appliqué', 'drift' => 'Exemple de dérive Redis', 'found' => 'Ce que nous avons trouvé avant réparation', 'repair' => 'La commande de réparation créée', 'operators' => 'Exemples pour les futurs opérateurs', 'exit' => 'Codes de sortie CI/CD et opérations', 'actions' => 'Actions effectuées', 'outcome' => 'Résultat final', 'why' => 'Pourquoi c’est important', 'not' => 'Ce que ce n’était pas', 'future' => 'Procédure opérationnelle future',
    'summary_rows' => [['Zone touchée', 'Connexions PayCal Business et index de recherche Redis'], ['Problème', 'D’anciens ensembles Redis dérivés pouvaient mélanger adhésion active, état en attente et anciennes entrées'], ['Correction principale', 'Les hashes de connexion restent canoniques; membres actifs, connexions en attente et index non terminaux sont séparés'], ['Outil opérationnel', '<code>scripts/paycal business:connections:audit</code> audite et répare la dérive'], ['Avant réparation', '18 349 dérives réparables connues, zéro violation propriétaire, zéro dérive inconnue'], ['Après réparation', '0 dérive, 0 violation propriétaire, 0 dérive inconnue']],
    'problem_p' => ['PayCal Business utilise Redis pour des recherches rapides. L’enregistrement canonique est un hash comme <code>business:connection:{businessId}:{userUUID}</code>, qui stocke rôle, statut, scopes, horodatages, métadonnées d’invitation ou de demande et champs d’audit.', 'Le problème n’était pas le hash canonique, mais la dérive sémantique dans des ensembles Redis dérivés. Historiquement, <code>business:members:{businessId}</code> et <code>business:user:{userUUID}</code> pouvaient servir d’index connexionnels larges, ce qui permettait à du code ancien de traiter une connexion en attente ou obsolète comme active.', 'La distinction compte. Une demande en attente n’est pas une adhésion active. Une connexion acceptée ne donne pas automatiquement accès aux données chiffrées sans consentement et état key-wrap valides. Un ensemble Redis dérivé n’est pas une autorité; c’est un index remplaçable.'],
    'model_intro' => 'Le modèle renforcé sépare le cycle de vie de l’accès actif:', 'model_after' => 'Chaque ensemble Redis a ainsi une seule signification. Les compteurs utilisent les index actifs, les files en attente utilisent <code>business:pending:{businessId}</code>, et l’interface peut lire les connexions sans accorder d’accès actif.', 'drift_intro' => 'Une entrée d’index obsolète ressemble à ceci:', 'drift_second' => 'Autre exemple réparable: une connexion active sans ses index actifs:', 'drift_after' => 'Dans les deux cas, la réparation ne crée pas de connexion. Elle aligne seulement les ensembles dérivés sur le hash canonique.', 'found_intro' => 'L’audit en lecture seule a trouvé beaucoup de dérive d’index, mais aucune corruption d’autorité et aucun état terminal dans les index vivants.', 'found_conclusion' => 'Conclusion importante: l’audit a trouvé des résidus de migration dans les index dérivés, sans preuve de fuite d’états terminaux, de corruption de rôle propriétaire ou de dérive inconnue. Le graphe canonique et l’autorité propriétaire étaient intacts.',
    'repair_p' => ['Nous avons créé <code>scripts/connections-audit.php</code> et l’avons exposé via le dispatcher interne existant:', 'La commande a deux modes:', 'La commande ne choisit jamais automatiquement un propriétaire, ne réécrit jamais l’autorité des rôles et ne crée jamais de connexion canonique. Elle réconcilie seulement des index jetables avec des hashes canoniques.'], 'modes' => ['<strong>Rapport seul:</strong> scanne les hashes et signale la dérive sans modifier Redis.', '<strong>Réparation:</strong> corrige les dérives connues en ajoutant ou retirant des membres d’ensembles Redis.'], 'operator_labels' => ['Lancer un audit complet en rapport seul', 'Lancer un rapport pour un Business', 'Enregistrer l’artefact avant réparation', 'Réparer la dérive connue', 'Relancer le rapport et attendre un résultat propre', 'Résumé sain attendu après réparation'], 'exit_intro' => 'La commande est lisible par machine pour que CI et opérations distinguent une dérive réparable d’une condition dangereuse.', 'exit_after' => 'Elle peut donc servir aux contrôles planifiés et aux gates de déploiement. Un job rapport seul peut sortir en <code>1</code>; un job de réparation peut sortir en <code>0</code> après correction.',
    'actions_l' => ['Séparation des index d’adhésion active et des index de cycle de vie.', 'Ajout des index non terminaux <code>business:connections:{businessId}</code> et <code>business:connections:user:{userUUID}</code>.', 'Ajout de <code>business:pending:{businessId}</code> pour les workflows en attente.', 'Centralisation de la maintenance des ensembles dérivés.', 'Durcissement des transitions terminales.', 'Ajout des versions de scope et de politique.', 'Mise à jour des scripts de seed et consolidation.', 'Création de la commande avec rapport, réparation, JSON, buckets et codes CI.', 'Rapport, réparation, nouveau rapport et vérification finale.', 'Exécution de la suite PHPUnit ciblée.'], 'outcome_p' => ['La séquence finale a produit ce résultat:', 'La suite ciblée a réussi après réparation:', 'Après les tests ciblés, une passe supplémentaire a confirmé que les index créés par fixtures étaient propres. Audit final:'], 'why_p' => ['La collaboration payroll est sensible. Tableaux de bord, invitations, approbations, consentement de chiffrement et révocation dépendent de sémantiques propres.', 'Le nouveau modèle donne à PayCal une frontière opérationnelle plus forte:', 'Cette séparation garde les compteurs exacts, évite de traiter l’attente comme accès actif, exclut les états terminaux des index vivants et permet une réparation Redis sans mutation d’autorité.'], 'not_l' => ['Pas une corruption du rôle propriétaire: zéro violation.', 'Pas une fuite d’utilisateurs révoqués ou rejetés: buckets terminaux à zéro.', 'Pas une réécriture des connexions canoniques: seuls les index dérivés ont été réconciliés.', 'Pas un changement de recherche publique: elle reste gouvernée par les politiques existantes.'], 'future_l' => ['Prendre un snapshot ou backup Redis.', 'Exécuter le rapport seul et garder le JSON.', 'Exécuter <code>--fix</code> d’abord hors production.', 'Relancer et confirmer <code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code>.', 'Exécuter les tests ciblés.', 'Smoke-tester compteurs, demandes, invitations, approbations, révocations et recherche publique.', 'Réparer la production seulement pendant une fenêtre calme.'],
  ],
];

$localizedOverrides['it'] = $localizedOverrides['es'];
$localizedOverrides['it']['summary'] = 'Sintesi esecutiva';
$localizedOverrides['it']['problem'] = 'Il problema';
$localizedOverrides['it']['model'] = 'Il modello che ora applichiamo';
$localizedOverrides['it']['drift'] = 'Esempio di deriva Redis';
$localizedOverrides['it']['found'] = 'Cosa abbiamo trovato prima della riparazione';
$localizedOverrides['it']['repair'] = 'Il comando di riparazione creato';
$localizedOverrides['it']['operators'] = 'Esempi per operatori futuri';
$localizedOverrides['it']['exit'] = 'Codici di uscita CI/CD e operazioni';
$localizedOverrides['it']['actions'] = 'Azioni eseguite';
$localizedOverrides['it']['outcome'] = 'Risultato finale';
$localizedOverrides['it']['why'] = 'Perché è importante';
$localizedOverrides['it']['not'] = 'Cosa non era';
$localizedOverrides['it']['future'] = 'Procedura operativa futura';
$localizedOverrides['it']['summary_rows'] = [['Area interessata', 'Record del ciclo di vita delle connessioni PayCal Business e indici Redis'], ['Problema', 'Vecchi set Redis derivati potevano mescolare appartenenza attiva, stato in sospeso e voci storiche obsolete'], ['Correzione principale', 'Gli hash di connessione restano canonici; membri attivi, connessioni in sospeso e indici non terminali sono separati'], ['Strumento operativo', '<code>scripts/paycal business:connections:audit</code> verifica e ripara la deriva degli indici'], ['Prima della riparazione', '18.349 derive note riparabili, zero violazioni owner, zero deriva sconosciuta'], ['Dopo la riparazione', '0 deriva, 0 violazioni owner, 0 deriva sconosciuta']];
$localizedOverrides['it']['problem_p'] = ['PayCal Business usa Redis per lookup rapidi. Il record canonico è un hash come <code>business:connection:{businessId}:{userUUID}</code>, che conserva ruolo, stato, scope, timestamp, metadati di invito o richiesta e campi di audit.', 'Il problema non era l’hash canonico, ma la deriva semantica nei set Redis derivati. In passato <code>business:members:{businessId}</code> e <code>business:user:{userUUID}</code> potevano essere usati come indici ampi, facendo sembrare attiva una connessione in sospeso o obsoleta.', 'La distinzione conta. Una richiesta in sospeso non è appartenenza attiva. Una connessione accettata non dà automaticamente accesso ai dati cifrati senza consenso e stato key-wrap validi. Un set Redis derivato non è autorità; è un indice sostituibile.'];
$localizedOverrides['it']['model_intro'] = 'Il modello rafforzato separa ciclo di vita e accesso attivo:';
$localizedOverrides['it']['model_after'] = 'Ogni set Redis ha così un solo significato. I conteggi dashboard usano gli indici attivi, le code pendenti usano <code>business:pending:{businessId}</code> e la UI può leggere connessioni senza concedere accesso attivo.';
$localizedOverrides['it']['drift_intro'] = 'Una voce di indice obsoleta appare così:';
$localizedOverrides['it']['drift_second'] = 'Un altro esempio riparabile è una connessione attiva priva degli indici attivi:';
$localizedOverrides['it']['drift_after'] = 'In entrambi i casi la riparazione non crea una connessione; riconcilia solo set derivati con l’hash canonico.';
$localizedOverrides['it']['found_intro'] = 'L’audit in sola lettura ha trovato molta deriva di indici, ma nessuna corruzione di autorità e nessuno stato terminale negli indici live.';
$localizedOverrides['it']['found_conclusion'] = 'La conclusione era importante: l’audit ha trovato residui di migrazione negli indici derivati, senza evidenza di leakage terminale, corruzione del ruolo owner o deriva sconosciuta.';
$localizedOverrides['it']['repair_p'] = ['Abbiamo creato <code>scripts/connections-audit.php</code> e lo abbiamo esposto tramite il dispatcher interno:', 'Il comando ha due modalità:', 'Il comando non sceglie mai owner automaticamente, non riscrive autorità di ruolo e non crea connessioni canoniche. Riconcilia solo indici usa e getta con hash canonici.'];
$localizedOverrides['it']['modes'] = ['<strong>Solo report:</strong> scansiona gli hash e segnala deriva senza modificare Redis.', '<strong>Riparazione:</strong> corregge derive note aggiungendo o rimuovendo membri dai set Redis.'];
$localizedOverrides['it']['operator_labels'] = ['Eseguire audit completo solo report', 'Eseguire report per un Business', 'Salvare artefatto prima della riparazione', 'Riparare deriva nota', 'Ripetere il report e attendere risultato pulito', 'Riepilogo sano atteso dopo la riparazione'];
$localizedOverrides['it']['exit_intro'] = 'Il comando è leggibile da macchine così CI e operazioni distinguono deriva riparabile da condizioni non sicure.';
$localizedOverrides['it']['exit_after'] = 'Questo lo rende adatto a controlli programmati e gate di deploy. Un job solo report può uscire con <code>1</code>; un job di riparazione può uscire con <code>0</code>.';
$localizedOverrides['it']['actions_l'] = ['Separati gli indici di appartenenza attiva dagli indici di ciclo di vita.', 'Aggiunti gli indici non terminali <code>business:connections:{businessId}</code> e <code>business:connections:user:{userUUID}</code>.', 'Aggiunto <code>business:pending:{businessId}</code> per i workflow in sospeso.', 'Centralizzata la manutenzione dei set derivati.', 'Rafforzate le transizioni terminali.', 'Aggiunti campi di versione scope e policy.', 'Aggiornati seed e consolidamento.', 'Creato comando con report, fix, JSON, bucket e codici CI.', 'Eseguiti report, fix, nuovo report e verifica finale.', 'Eseguita la suite PHPUnit mirata.'];
$localizedOverrides['it']['outcome_p'] = ['La sequenza finale ha prodotto questo risultato:', 'La suite mirata è passata dopo la riparazione:', 'Dopo i test mirati, un’ulteriore passata ha confermato che gli indici creati dai fixture erano puliti. Audit finale:'];
$localizedOverrides['it']['why_p'] = ['La collaborazione payroll è sensibile alla sicurezza. Dashboard, inviti, approvazioni, consenso alla cifratura e revoca dipendono da semantiche pulite.', 'Il nuovo modello dà a PayCal un confine operativo più forte:', 'La separazione mantiene corretti i conteggi, impedisce di trattare pending come accesso attivo, esclude stati terminali dagli indici live e consente riparazioni Redis senza mutare autorità.'];
$localizedOverrides['it']['not_l'] = ['Non era corruzione del ruolo owner: zero violazioni.', 'Non era leakage di utenti revocati o rifiutati: bucket terminali a zero.', 'Non era riscrittura delle connessioni canoniche: solo indici derivati riconciliati.', 'Non era modifica della ricerca pubblica, che resta governata dalle policy esistenti.'];
$localizedOverrides['it']['future_l'] = ['Prendere snapshot o backup Redis.', 'Eseguire solo report e conservare il JSON.', 'Eseguire <code>--fix</code> prima fuori produzione.', 'Rilanciare e confermare <code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code>.', 'Eseguire test mirati.', 'Smoke test su conteggi, richieste, inviti, approvazioni, revoche e ricerca pubblica.', 'Riparare produzione solo in una finestra tranquilla.'];
$localizedOverrides['nl'] = $localizedOverrides['fr'];
$localizedOverrides['nl']['summary'] = 'Managementsamenvatting';
$localizedOverrides['nl']['problem'] = 'Het probleem';
$localizedOverrides['nl']['model'] = 'Het model dat we nu afdwingen';
$localizedOverrides['nl']['drift'] = 'Voorbeeld van Redis-drift';
$localizedOverrides['nl']['found'] = 'Wat we voor herstel vonden';
$localizedOverrides['nl']['repair'] = 'De herstelopdracht die we bouwden';
$localizedOverrides['nl']['operators'] = 'Voorbeelden voor toekomstige operators';
$localizedOverrides['nl']['exit'] = 'Exitcodes voor CI/CD en operations';
$localizedOverrides['nl']['actions'] = 'Uitgevoerde acties';
$localizedOverrides['nl']['outcome'] = 'Eindresultaat';
$localizedOverrides['nl']['why'] = 'Waarom dit belangrijk is';
$localizedOverrides['nl']['not'] = 'Wat dit niet was';
$localizedOverrides['nl']['future'] = 'Toekomstige werkwijze';
$localizedOverrides['nl']['summary_rows'] = [['Betrokken gebied', 'PayCal Business-verbindingen en Redis-lookupindexen'], ['Probleem', 'Oude afgeleide Redis-sets konden actief lidmaatschap, wachtstatus en verouderde items mengen'], ['Kernoplossing', 'Verbindingshashes blijven canoniek; actieve leden, wachtende verbindingen en niet-terminale indexen zijn gescheiden'], ['Operationele tool', '<code>scripts/paycal business:connections:audit</code> controleert en herstelt indexdrift'], ['Voor herstel', '18.349 bekende herstelbare driftitems, nul eigenaarsschendingen, nul onbekende drift'], ['Na herstel', '0 drift, 0 eigenaarsschendingen, 0 onbekende drift']];
$localizedOverrides['nl']['problem_p'] = ['PayCal Business gebruikt Redis voor snelle verbinding-lookups. Het canonieke record is een hash zoals <code>business:connection:{businessId}:{userUUID}</code> met rol, status, scopes, tijdstempels, uitnodigings- of aanvraagmetadata en auditvelden.', 'Het probleem was niet de canonieke hash, maar semantische drift in afgeleide Redis-sets. Vroeger konden <code>business:members:{businessId}</code> en <code>business:user:{userUUID}</code> als brede verbinding-indexen worden gebruikt, waardoor oude code een wachtende of oude verbinding als actief kon behandelen.', 'Dat onderscheid is belangrijk. Een wachtende aanvraag is geen actief lidmaatschap. Een geaccepteerde verbinding geeft niet automatisch versleutelde gegevenstoegang zonder geldige toestemming en key-wrapstatus. Een afgeleide Redis-set is geen autoriteit; het is een vervangbare index.'];
$localizedOverrides['nl']['model_intro'] = 'Het geharde model scheidt levenscyclusstatus van actieve toegang:';
$localizedOverrides['nl']['model_after'] = 'Elke Redis-set heeft nu één betekenis. Dashboardtellingen gebruiken actieve indexen, wachtrijen gebruiken <code>business:pending:{businessId}</code>, en de UI kan verbindingen lezen zonder actieve toegang te geven.';
$localizedOverrides['nl']['drift_intro'] = 'Een verouderde indexentry ziet er zo uit:';
$localizedOverrides['nl']['drift_second'] = 'Een tweede herstelbaar voorbeeld is een actieve verbinding zonder actieve indexen:';
$localizedOverrides['nl']['drift_after'] = 'In beide gevallen maakt herstel geen verbinding aan; het stemt afgeleide sets af op de canonieke hash.';
$localizedOverrides['nl']['found_intro'] = 'De rapport-audit vond veel afgeleide indexdrift, maar geen autoriteitscorruptie en geen terminale status in live indexen.';
$localizedOverrides['nl']['found_conclusion'] = 'De conclusie was belangrijk: het ging om migratieresten in afgeleide indexen, zonder bewijs van terminale lekkage, eigenaarrolcorruptie of onbekende drift.';
$localizedOverrides['nl']['repair_p'] = ['We maakten <code>scripts/connections-audit.php</code> en ontsloten dit via de interne command dispatcher:', 'De opdracht heeft twee modi:', 'De opdracht kiest nooit automatisch eigenaars, herschrijft geen rolautoriteit en maakt geen canonieke verbindingen. Ze stemt alleen wegwerpindexen af op canonieke hashes.'];
$localizedOverrides['nl']['modes'] = ['<strong>Alleen rapport:</strong> scant hashes en meldt drift zonder Redis te wijzigen.', '<strong>Herstel:</strong> corrigeert bekende drift door leden in Redis-sets toe te voegen of te verwijderen.'];
$localizedOverrides['nl']['operator_labels'] = ['Volledige rapport-audit uitvoeren', 'Rapport voor één Business uitvoeren', 'Artefact voor herstel opslaan', 'Bekende drift herstellen', 'Rapport opnieuw draaien en schoon resultaat verwachten', 'Verwachte gezonde samenvatting na herstel'];
$localizedOverrides['nl']['exit_intro'] = 'De opdracht is machineleesbaar zodat CI en operations herstelbare drift onderscheiden van onveilige omstandigheden.';
$localizedOverrides['nl']['exit_after'] = 'De tool past daardoor in geplande checks en deploy-gates. Een rapportjob kan <code>1</code> teruggeven; een hersteljob kan na herstel <code>0</code> teruggeven.';
$localizedOverrides['nl']['actions_l'] = ['Actieve lidindexen gescheiden van levenscyclusindexen.', 'Niet-terminale indexen toegevoegd.', '<code>business:pending:{businessId}</code> toegevoegd.', 'Centraal verbindingpad bijgewerkt.', 'Terminale overgangen gehard.', 'Scope- en policyversies toegevoegd.', 'Seed- en consolidatiescripts bijgewerkt.', 'Reconciliation-command gebouwd.', 'Rapport, herstel en eindcontrole uitgevoerd.', 'Gerichte PHPUnit-suite uitgevoerd.'];
$localizedOverrides['nl']['outcome_p'] = ['De laatste herstelreeks gaf dit resultaat:', 'De gerichte testsuite slaagde na herstel:', 'Na de gerichte tests bevestigde een extra pass dat fixture-indexen schoon waren. Finale audit:'];
$localizedOverrides['nl']['why_p'] = ['Payroll-samenwerking is securitygevoelig. Dashboards, uitnodigingen, goedkeuringen, encryptietoestemming en intrekking hangen af van schone verbindingenemantiek.', 'Het nieuwe model geeft PayCal een sterkere operationele grens:', 'Die scheiding houdt tellingen correct, voorkomt dat pending als actief geldt, houdt terminale statussen uit live indexen en maakt veilig Redis-herstel mogelijk.'];
$localizedOverrides['nl']['not_l'] = ['Geen eigenaarrolcorruptie: nul schendingen.', 'Geen lek van ingetrokken of afgewezen gebruikers: terminale buckets waren nul.', 'Geen herschrijving van canonieke verbindingen: alleen afgeleide indexen.', 'Geen wijziging aan openbaar zoekbeleid.'];
$localizedOverrides['nl']['future_l'] = ['Maak Redis-snapshot of backup.', 'Draai rapport en bewaar JSON.', 'Draai <code>--fix</code> eerst buiten productie.', 'Bevestig <code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code>.', 'Draai gerichte tests.', 'Smoke-test tellingen, aanvragen, uitnodigingen, goedkeuringen, intrekking en publieke zoekfunctie.', 'Herstel productie alleen in een rustig venster.'];
$localizedOverrides['pt'] = $localizedOverrides['es'];
$localizedOverrides['pt']['summary'] = 'Resumo executivo';
$localizedOverrides['pt']['problem'] = 'O problema';
$localizedOverrides['pt']['model'] = 'O modelo que agora aplicamos';
$localizedOverrides['pt']['drift'] = 'Exemplo de deriva no Redis';
$localizedOverrides['pt']['found'] = 'O que encontramos antes do reparo';
$localizedOverrides['pt']['repair'] = 'O comando de reparo que criamos';
$localizedOverrides['pt']['operators'] = 'Exemplos para operadores futuros';
$localizedOverrides['pt']['exit'] = 'Códigos de saída de CI/CD e operações';
$localizedOverrides['pt']['actions'] = 'Ações realizadas';
$localizedOverrides['pt']['outcome'] = 'Resultado final';
$localizedOverrides['pt']['why'] = 'Por que isso importa';
$localizedOverrides['pt']['not'] = 'O que isso não foi';
$localizedOverrides['pt']['future'] = 'Procedimento operacional futuro';
$localizedOverrides['pt']['summary_rows'] = [['Área afetada', 'Registros de conexão do PayCal Business e índices Redis'], ['Problema', 'Conjuntos Redis derivados antigos podiam misturar associação ativa, estado pendente e entradas antigas'], ['Correção principal', 'Hashes de conexão permanecem canônicos; membros ativos, pendentes e índices não terminais são separados'], ['Ferramenta operacional', '<code>scripts/paycal business:connections:audit</code> audita e repara deriva'], ['Antes do reparo', '18.349 achados reparáveis conhecidos, zero violações de proprietário, zero deriva desconhecida'], ['Depois do reparo', '0 deriva, 0 violações de proprietário, 0 deriva desconhecida']];
$localizedOverrides['pt']['problem_p'] = ['O PayCal Business usa Redis para buscas rápidas. O registro canônico é um hash como <code>business:connection:{businessId}:{userUUID}</code>, com papel, status, escopos, timestamps, metadados e campos de auditoria.', 'O problema não era o hash canônico, mas a deriva semântica em conjuntos Redis derivados. Antes, <code>business:members:{businessId}</code> e <code>business:user:{userUUID}</code> podiam ser usados como índices amplos, fazendo uma relação pendente ou antiga parecer ativa.', 'Essa distinção importa. Uma solicitação pendente não é associação ativa. Uma relação aceita não dá acesso criptografado automaticamente sem consentimento e key-wrap válidos. Um conjunto Redis derivado não é autoridade; é um índice substituível.'];
$localizedOverrides['pt']['model_intro'] = 'O modelo reforçado separa ciclo de vida de acesso ativo:';
$localizedOverrides['pt']['model_after'] = 'Cada conjunto Redis passa a ter um único significado. Dashboards usam índices ativos, filas pendentes usam <code>business:pending:{businessId}</code> e a UI pode ler relações sem conceder acesso ativo.';
$localizedOverrides['pt']['drift_intro'] = 'Uma entrada obsoleta de índice aparece assim:';
$localizedOverrides['pt']['drift_second'] = 'Outro exemplo reparável é uma relação ativa sem seus índices ativos:';
$localizedOverrides['pt']['drift_after'] = 'Nos dois casos, o reparo não cria relação. Ele apenas reconcilia conjuntos derivados com o hash canônico.';
$localizedOverrides['pt']['found_intro'] = 'A auditoria em modo relatório encontrou muita deriva de índices, mas nenhuma corrupção de autoridade e nenhum estado terminal em índices vivos.';
$localizedOverrides['pt']['found_conclusion'] = 'A conclusão foi importante: havia resíduo de migração em índices derivados, sem evidência de vazamento terminal, corrupção de proprietário ou deriva desconhecida.';
$localizedOverrides['pt']['repair_p'] = ['Criamos <code>scripts/connections-audit.php</code> e expusemos pelo dispatcher interno:', 'O comando tem dois modos:', 'O comando nunca escolhe proprietários automaticamente, não reescreve autoridade de papéis e não cria relações canônicas. Ele só reconcilia índices descartáveis com hashes canônicos.'];
$localizedOverrides['pt']['modes'] = ['<strong>Somente relatório:</strong> varre hashes e reporta deriva sem alterar Redis.', '<strong>Reparo:</strong> corrige deriva conhecida adicionando ou removendo membros de conjuntos Redis.'];
$localizedOverrides['pt']['operator_labels'] = ['Executar auditoria completa só relatório', 'Executar relatório para um negócio', 'Salvar artefato antes do reparo', 'Reparar deriva conhecida', 'Reexecutar relatório e esperar resultado limpo', 'Resumo saudável esperado após reparo'];
$localizedOverrides['pt']['exit_intro'] = 'O comando é legível por máquina para que CI e operações diferenciem deriva reparável de condições inseguras.';
$localizedOverrides['pt']['exit_after'] = 'Assim, ele serve para verificações agendadas e gates de deploy. Um job só relatório pode sair com <code>1</code>; um job de reparo pode sair com <code>0</code>.';
$localizedOverrides['pt']['actions_l'] = ['Separamos índices ativos dos índices de ciclo de vida.', 'Adicionamos índices não terminais.', 'Adicionamos <code>business:pending:{businessId}</code>.', 'Centralizamos a manutenção dos conjuntos derivados.', 'Reforçamos transições terminais.', 'Adicionamos versões de escopo e política.', 'Atualizamos scripts de seed e consolidação.', 'Criamos comando com relatório, fix, JSON, buckets e códigos CI.', 'Executamos relatório, reparo e verificação final.', 'Executamos a suíte PHPUnit direcionada.'];
$localizedOverrides['pt']['outcome_p'] = ['A sequência final produziu este resultado:', 'A suíte direcionada passou após o reparo:', 'Após os testes direcionados, uma passada extra confirmou que índices criados por fixtures estavam limpos. Auditoria final:'];
$localizedOverrides['pt']['why_p'] = ['Colaboração de folha de pagamento é sensível à segurança. Dashboards, convites, aprovações, consentimento de criptografia e revogação dependem de semântica limpa.', 'O novo modelo dá ao PayCal uma fronteira operacional mais forte:', 'A separação mantém contagens corretas, impede pendentes como ativos, remove terminais de índices vivos e permite reparo seguro no Redis.'];
$localizedOverrides['pt']['not_l'] = ['Não foi corrupção de proprietário: zero violações.', 'Não foi vazamento de usuários revogados ou rejeitados: buckets terminais em zero.', 'Não foi reescrita de relações canônicas: só índices derivados.', 'Não foi mudança na política de busca pública.'];
$localizedOverrides['pt']['future_l'] = ['Criar snapshot ou backup do Redis.', 'Executar relatório e salvar JSON.', 'Executar <code>--fix</code> primeiro fora de produção.', 'Confirmar <code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code>.', 'Executar testes direcionados.', 'Smoke-test de contagens, solicitações, convites, aprovações, revogação e busca pública.', 'Reparar produção apenas em janela tranquila.'];
$localizedOverrides['tl'] = $localizedOverrides['es'];
$localizedOverrides['tl']['summary'] = 'Executive summary';
$localizedOverrides['tl']['problem'] = 'Ang problema';
$localizedOverrides['tl']['model'] = 'Ang modelong ipinapatupad na namin';
$localizedOverrides['tl']['drift'] = 'Halimbawa ng Redis drift';
$localizedOverrides['tl']['found'] = 'Ang nakita namin bago ang repair';
$localizedOverrides['tl']['repair'] = 'Ang repair command na ginawa namin';
$localizedOverrides['tl']['operators'] = 'Mga halimbawa para sa susunod na operators';
$localizedOverrides['tl']['exit'] = 'CI/CD at ops exit codes';
$localizedOverrides['tl']['actions'] = 'Mga ginawa naming aksyon';
$localizedOverrides['tl']['outcome'] = 'Final outcome';
$localizedOverrides['tl']['why'] = 'Bakit mahalaga ito';
$localizedOverrides['tl']['not'] = 'Hindi ito ganito';
$localizedOverrides['tl']['future'] = 'Future operating procedure';
$localizedOverrides['tl']['summary_rows'] = [['Apektadong bahagi', 'PayCal Business connection lifecycle records at Redis lookup indexes'], ['Problema', 'Lumang derived Redis sets ay puwedeng maghalo ng active membership, pending workflow state, at stale historical entries'], ['Core fix', 'Canonical pa rin ang connection hashes; hiwalay ang active members, pending connections, at non-terminal lookup indexes'], ['Ops tool', '<code>scripts/paycal business:connections:audit</code> ang nag-audit at nag-aayos ng drift'], ['Bago ayusin', '18,349 known repairable drift, zero owner violations, zero unknown drift'], ['Pagkatapos ayusin', '0 drift, 0 owner violations, 0 unknown drift']];
$localizedOverrides['tl']['problem_p'] = ['Gumagamit ang PayCal Business ng Redis para sa mabilis na connection lookup. Ang canonical record ay hash tulad ng <code>business:connection:{businessId}:{userUUID}</code> na may role, status, scopes, timestamps, metadata, at audit fields.', 'Hindi ang canonical hash ang problema. Ang problema ay semantic drift sa derived Redis sets. Dati, puwedeng gamitin ang <code>business:members:{businessId}</code> at <code>business:user:{userUUID}</code> bilang malawak na connection indexes, kaya madaling magmukhang active ang pending o stale connection.', 'Mahalaga ang pagkakaiba. Ang pending access request ay hindi active membership. Ang accepted connection ay hindi awtomatikong encrypted data access kung walang valid consent at key-wrap state. Ang derived Redis set ay hindi authority; index lang ito.'];
$localizedOverrides['tl']['model_intro'] = 'Pinaghihiwalay ng hardened model ang lifecycle state at active access state:';
$localizedOverrides['tl']['model_after'] = 'May isang kahulugan ang bawat Redis set. Active indexes ang para sa dashboard counts, <code>business:pending:{businessId}</code> ang para sa pending queues, at connection indexes ang para sa UI na hindi nagbibigay ng active access.';
$localizedOverrides['tl']['drift_intro'] = 'Ganito ang stale index entry:';
$localizedOverrides['tl']['drift_second'] = 'Isa pang repairable example ay active connection na kulang sa active indexes:';
$localizedOverrides['tl']['drift_after'] = 'Sa dalawang kaso, hindi gumagawa ng connection ang repair. Ina-align lang nito ang derived sets sa canonical hash.';
$localizedOverrides['tl']['found_intro'] = 'Nakakita ang report-only audit ng maraming derived-index drift, pero walang authority corruption at walang terminal state sa live indexes.';
$localizedOverrides['tl']['found_conclusion'] = 'Mahalaga ang conclusion: migration residue ang nakita, walang ebidensya ng terminal leakage, owner-role corruption, o unknown drift.';
$localizedOverrides['tl']['repair_p'] = ['Ginawa namin ang <code>scripts/connections-audit.php</code> at inilabas ito sa internal dispatcher:', 'May dalawang mode ang command:', 'Hindi ito auto-pumipili ng owner, hindi nagre-rewrite ng role authority, at hindi gumagawa ng canonical connections. Inaayos lang nito ang disposable indexes laban sa canonical hashes.'];
$localizedOverrides['tl']['modes'] = ['<strong>Report-only:</strong> nag-scan at nagre-report nang walang Redis mutation.', '<strong>Fix:</strong> nag-aayos ng known drift sa pamamagitan ng pagdagdag o pag-alis sa Redis sets.'];
$localizedOverrides['tl']['operator_labels'] = ['Patakbuhin ang full report-only audit', 'Patakbuhin ang report para sa isang business', 'I-save ang artifact bago mag-repair', 'Ayusin ang known drift', 'Patakbuhin ulit at asahan ang clean result', 'Expected healthy summary pagkatapos ng repair'];
$localizedOverrides['tl']['exit_intro'] = 'Ginawa naming machine-readable ang command para maiba ng CI at ops ang repairable drift sa unsafe conditions.';
$localizedOverrides['tl']['exit_after'] = 'Puwede itong gamitin sa scheduled checks at deployment gates. Report-only job ay puwedeng lumabas sa <code>1</code>; repair job ay puwedeng <code>0</code> kapag naayos.';
$localizedOverrides['tl']['actions_l'] = ['Hiniwalay ang active membership indexes.', 'Idinagdag ang non-terminal connection indexes.', 'Idinagdag ang <code>business:pending:{businessId}</code>.', 'In-update ang central connection writer.', 'Pinatibay ang terminal transitions.', 'Idinagdag ang scope at policy versions.', 'In-update ang seed at consolidation scripts.', 'Ginawa ang reconciliation command.', 'Nag-report, nag-fix, at nag-verify.', 'Pinatakbo ang targeted PHPUnit suite.'];
$localizedOverrides['tl']['outcome_p'] = ['Ito ang final repair result:', 'Pumasa ang targeted test suite pagkatapos ng repair:', 'Pagkatapos ng tests, isang extra pass ang nag-confirm na clean ang fixture-created indexes. Final audit:'];
$localizedOverrides['tl']['why_p'] = ['Security-sensitive ang payroll collaboration. Dashboard, invites, approvals, encryption consent, at revoke ay nakadepende sa malinis na connection semantics.', 'Mas malinaw na operational boundary ang bagong model:', 'Pinapanatili nitong tama ang counts, hindi ginagawang active ang pending, inaalis ang terminal states sa live indexes, at ligtas na nag-aayos ng Redis drift.'];
$localizedOverrides['tl']['not_l'] = ['Hindi ito owner-role corruption: zero violations.', 'Hindi ito leakage ng revoked o rejected users: zero terminal buckets.', 'Hindi ito rewrite ng canonical connections: derived indexes lang.', 'Hindi ito pagbabago sa public search policy.'];
$localizedOverrides['tl']['future_l'] = ['Gumawa ng Redis snapshot o backup.', 'Patakbuhin ang report at i-save ang JSON.', 'Patakbuhin muna ang <code>--fix</code> sa non-production.', 'I-confirm ang <code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code>.', 'Patakbuhin ang targeted tests.', 'Smoke-test counts, requests, invites, approvals, revoke, at public search.', 'Production repair lamang sa tahimik na window.'];
$localizedOverrides['tr'] = $localizedOverrides['fr'];
$localizedOverrides['tr']['summary'] = 'Yönetici özeti';
$localizedOverrides['tr']['problem'] = 'Sorun';
$localizedOverrides['tr']['model'] = 'Artık uyguladığımız model';
$localizedOverrides['tr']['drift'] = 'Redis sapması örneği';
$localizedOverrides['tr']['found'] = 'Onarımdan önce bulduklarımız';
$localizedOverrides['tr']['repair'] = 'Oluşturduğumuz onarım komutu';
$localizedOverrides['tr']['operators'] = 'Gelecekteki operatörler için örnekler';
$localizedOverrides['tr']['exit'] = 'CI/CD ve operasyon çıkış kodları';
$localizedOverrides['tr']['actions'] = 'Yaptığımız işlemler';
$localizedOverrides['tr']['outcome'] = 'Son sonuç';
$localizedOverrides['tr']['why'] = 'Neden önemli';
$localizedOverrides['tr']['not'] = 'Bu ne değildi';
$localizedOverrides['tr']['future'] = 'Gelecekteki işletim prosedürü';
$localizedOverrides['tr']['summary_rows'] = [['Etkilenen alan', 'PayCal Business bağlantı yaşam döngüsü kayıtları ve Redis indeksleri'], ['Sorun', 'Eski türetilmiş Redis setleri aktif üyelik, bekleyen durum ve eski girdileri karıştırabiliyordu'], ['Temel düzeltme', 'İlişki hashleri kanonik kaldı; aktif üyeler, bekleyen bağlantılar ve terminal olmayan indeksler ayrıldı'], ['Operasyon aracı', '<code>scripts/paycal business:connections:audit</code> sapmayı denetler ve onarır'], ['Onarım öncesi', '18.349 bilinen onarılabilir sapma, sıfır sahip ihlali, sıfır bilinmeyen sapma'], ['Onarım sonrası', '0 sapma, 0 sahip ihlali, 0 bilinmeyen sapma']];
$localizedOverrides['tr']['problem_p'] = ['PayCal Business hızlı aramalar için Redis kullanır. Kanonik kayıt <code>business:connection:{businessId}:{userUUID}</code> gibi bir hashtir ve rol, durum, kapsamlar, zaman damgaları, metadata ve audit alanlarını saklar.', 'Sorun kanonik hash değildi; türetilmiş Redis setlerinde semantik sapmaydı. Geçmişte <code>business:members:{businessId}</code> ve <code>business:user:{userUUID}</code> geniş bağlantı indeksleri gibi kullanılabiliyordu.', 'Bekleyen erişim isteği aktif üyelik değildir. Kabul edilmiş bağlantı, geçerli onay ve key-wrap yoksa otomatik şifreli veri erişimi değildir. Türetilmiş Redis seti otorite değil, değiştirilebilir indekstir.'];
$localizedOverrides['tr']['model_intro'] = 'Sertleştirilmiş model yaşam döngüsü ile aktif erişimi ayırır:';
$localizedOverrides['tr']['model_after'] = 'Her Redis setinin tek anlamı vardır. Dashboard sayımları aktif indeksleri, bekleyen kuyruklar <code>business:pending:{businessId}</code> kullanır; UI aktif erişim vermeden bağlantı indekslerini okuyabilir.';
$localizedOverrides['tr']['drift_intro'] = 'Eski bir indeks girdisi şöyle görünür:';
$localizedOverrides['tr']['drift_second'] = 'Bir diğer onarılabilir örnek, aktif indeksleri eksik olan aktif bağlantıdir:';
$localizedOverrides['tr']['drift_after'] = 'Her iki durumda da onarım bağlantı icat etmez; türetilmiş setleri kanonik hash ile uzlaştırır.';
$localizedOverrides['tr']['found_intro'] = 'Salt rapor denetimi çok sayıda indeks sapması buldu, ancak otorite bozulması veya canlı indekslerde terminal durum bulmadı.';
$localizedOverrides['tr']['found_conclusion'] = 'Sonuç önemliydi: denetim türetilmiş indekslerde migrasyon artığı buldu; terminal sızıntı, sahip rolü bozulması veya bilinmeyen sapma kanıtı yoktu.';
$localizedOverrides['tr']['repair_p'] = ['<code>scripts/connections-audit.php</code> oluşturuldu ve iç komut dağıtıcısına eklendi:', 'Komutun iki modu vardır:', 'Komut otomatik sahip seçmez, rol otoritesini yeniden yazmaz ve kanonik bağlantı oluşturmaz. Yalnızca atılabilir indeksleri kanonik hashlerle uzlaştırır.'];
$localizedOverrides['tr']['modes'] = ['<strong>Yalnız rapor:</strong> Redis’i değiştirmeden hashleri tarar ve sapmayı raporlar.', '<strong>Onarım:</strong> bilinen sapmayı Redis set üyeleri ekleyip çıkararak düzeltir.'];
$localizedOverrides['tr']['operator_labels'] = ['Tam rapor denetimi çalıştır', 'Tek işletme için rapor çalıştır', 'Onarım öncesi artefaktı kaydet', 'Bilinen sapmayı onar', 'Raporu tekrar çalıştır ve temiz sonuç bekle', 'Onarım sonrası beklenen sağlıklı özet'];
$localizedOverrides['tr']['exit_intro'] = 'Komut makine tarafından okunabilir hale getirildi, böylece CI ve operasyonlar onarılabilir sapmayı güvensiz durumlardan ayırabilir.';
$localizedOverrides['tr']['exit_after'] = 'Bu, aracı planlı kontroller ve dağıtım kapıları için uygun yapar. Rapor jobı <code>1</code>, onarım jobı başarıdan sonra <code>0</code> dönebilir.';
$localizedOverrides['tr']['actions_l'] = ['Aktif üyelik indeksleri ayrıldı.', 'Terminal olmayan bağlantı indeksleri eklendi.', '<code>business:pending:{businessId}</code> eklendi.', 'Merkezi bağlantı yazıcı güncellendi.', 'Terminal geçişler sertleştirildi.', 'Kapsam ve politika sürümleri eklendi.', 'Seed ve konsolidasyon scriptleri güncellendi.', 'Reconciliation komutu oluşturuldu.', 'Rapor, onarım ve doğrulama yapıldı.', 'Hedefli PHPUnit paketi çalıştırıldı.'];
$localizedOverrides['tr']['outcome_p'] = ['Son onarım dizisi bu sonucu verdi:', 'Hedefli test paketi onarımdan sonra geçti:', 'Testlerden sonra ek bir geçiş fixture indekslerinin temiz olduğunu doğruladı. Son denetim:'];
$localizedOverrides['tr']['why_p'] = ['Payroll işbirliği güvenlik açısından hassastır. Dashboardlar, davetler, onaylar, şifreleme rızası ve iptal temiz bağlantı semantiğine bağlıdır.', 'Yeni model PayCal’a daha güçlü bir operasyon sınırı verir:', 'Bu ayrım sayımları doğru tutar, bekleyeni aktif erişim yapmaz, terminal durumları canlı indekslerden çıkarır ve güvenli Redis onarımı sağlar.'];
$localizedOverrides['tr']['not_l'] = ['Sahip rolü bozulması değildi: sıfır ihlal.', 'İptal veya reddedilmiş kullanıcı sızıntısı değildi: terminal bucketlar sıfırdı.', 'Kanonik bağlantılar yeniden yazılmadı: yalnızca türetilmiş indeksler uzlaştırıldı.', 'Genel arama politikası değişmedi.'];
$localizedOverrides['tr']['future_l'] = ['Redis snapshot veya backup al.', 'Raporu çalıştır ve JSON’u sakla.', '<code>--fix</code> önce production dışında çalıştır.', '<code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code> doğrula.', 'Hedefli testleri çalıştır.', 'Sayımlar, istekler, davetler, onaylar, iptal ve genel aramayı smoke-test et.', 'Production onarımını yalnız sakin pencerede yap.'];
$localizedOverrides['hi'] = $localizedOverrides['es'];
$localizedOverrides['hi']['summary'] = 'कार्यकारी सारांश';
$localizedOverrides['hi']['problem'] = 'समस्या';
$localizedOverrides['hi']['model'] = 'अब लागू किया गया मॉडल';
$localizedOverrides['hi']['drift'] = 'Redis drift का उदाहरण';
$localizedOverrides['hi']['found'] = 'Repair से पहले हमने क्या पाया';
$localizedOverrides['hi']['repair'] = 'हमने जो repair command बनाया';
$localizedOverrides['hi']['operators'] = 'भविष्य के operators के लिए उदाहरण';
$localizedOverrides['hi']['exit'] = 'CI/CD और ops exit codes';
$localizedOverrides['hi']['actions'] = 'हमने जो actions लिए';
$localizedOverrides['hi']['outcome'] = 'अंतिम परिणाम';
$localizedOverrides['hi']['why'] = 'यह क्यों महत्वपूर्ण है';
$localizedOverrides['hi']['not'] = 'यह क्या नहीं था';
$localizedOverrides['hi']['future'] = 'भविष्य की operating procedure';
$localizedOverrides['hi']['summary_rows'] = [['प्रभावित क्षेत्र', 'PayCal Business connection lifecycle records और Redis lookup indexes'], ['समस्या', 'पुराने derived Redis sets active membership, pending state और stale entries को मिला सकते थे'], ['मुख्य सुधार', 'Connection hashes canonical रहे; active members, pending connections और non-terminal indexes अलग किए गए'], ['Ops tool', '<code>scripts/paycal business:connections:audit</code> drift को audit और repair करता है'], ['Repair से पहले', '18,349 known repairable drift, zero owner violations, zero unknown drift'], ['Repair के बाद', '0 drift, 0 owner violations, 0 unknown drift']];
$localizedOverrides['hi']['problem_p'] = ['PayCal Business तेज connection lookup के लिए Redis का उपयोग करता है। Canonical record <code>business:connection:{businessId}:{userUUID}</code> जैसा hash है, जिसमें role, status, scopes, timestamps, metadata और audit fields रहते हैं।', 'समस्या canonical hash नहीं थी। समस्या derived Redis sets में semantic drift थी। पहले <code>business:members:{businessId}</code> और <code>business:user:{userUUID}</code> को broad connection indexes की तरह उपयोग किया जा सकता था, जिससे pending या stale connection active membership जैसी दिख सकती थी।', 'यह अंतर महत्वपूर्ण है। Pending access request active membership नहीं है। Accepted connection valid consent और key-wrap state के बिना encrypted data access नहीं देता। Derived Redis set authority नहीं है; यह replaceable index है।'];
$localizedOverrides['hi']['model_intro'] = 'Hardened model lifecycle state को active access state से अलग करता है:';
$localizedOverrides['hi']['model_after'] = 'अब हर Redis set का एक अर्थ है। Dashboard counts active indexes पर भरोसा करते हैं, pending queues <code>business:pending:{businessId}</code> इस्तेमाल करती हैं, और UI active access दिए बिना connection indexes पढ़ सकती है।';
$localizedOverrides['hi']['drift_intro'] = 'Stale index entry ऐसा दिखता है:';
$localizedOverrides['hi']['drift_second'] = 'एक और repairable example active connection है जिसके active indexes गायब हैं:';
$localizedOverrides['hi']['drift_after'] = 'दोनों मामलों में repair नई connection नहीं बनाता। वह केवल derived sets को canonical hash से reconcile करता है।';
$localizedOverrides['hi']['found_intro'] = 'Report-only audit ने बहुत derived-index drift पाया, लेकिन authority corruption नहीं और live indexes में terminal state नहीं।';
$localizedOverrides['hi']['found_conclusion'] = 'निष्कर्ष महत्वपूर्ण था: audit ने derived indexes में migration residue पाया, लेकिन terminal-state leakage, owner-role corruption या unknown drift का कोई evidence नहीं मिला।';
$localizedOverrides['hi']['repair_p'] = ['हमने <code>scripts/connections-audit.php</code> बनाया और इसे existing internal command dispatcher से expose किया:', 'Command के दो modes हैं:', 'Command कभी owner auto-select नहीं करता, role authority rewrite नहीं करता, और canonical connections नहीं बनाता। यह केवल disposable indexes को canonical hashes से reconcile करता है।'];
$localizedOverrides['hi']['modes'] = ['<strong>Report-only:</strong> hashes scan करता है और Redis mutate किए बिना drift report करता है।', '<strong>Fix:</strong> Redis set members add/remove करके known drift repair करता है।'];
$localizedOverrides['hi']['operator_labels'] = ['Full report-only audit चलाएँ', 'Single business report चलाएँ', 'Repair से पहले artifact save करें', 'Known drift repair करें', 'Report दोबारा चलाएँ और clean result expect करें', 'Repair के बाद expected healthy summary'];
$localizedOverrides['hi']['exit_intro'] = 'Command machine-readable है ताकि CI और ops normal repairable drift को unsafe conditions से अलग कर सकें।';
$localizedOverrides['hi']['exit_after'] = 'यह scheduled checks और deployment gates में उपयोगी है। Report-only job drift पर <code>1</code> दे सकता है; repair job सफल repair के बाद <code>0</code> दे सकता है।';
$localizedOverrides['hi']['actions_l'] = ['Active membership indexes को lifecycle indexes से अलग किया।', 'Non-terminal connection indexes जोड़े।', '<code>business:pending:{businessId}</code> जोड़ा।', 'Central connection writer update किया।', 'Terminal transitions harden किए।', 'Scope और policy versions जोड़े।', 'Seed और consolidation scripts update किए।', 'Reconciliation command बनाया।', 'Report, fix और final verification चलाया।', 'Targeted PHPUnit suite चलाई।'];
$localizedOverrides['hi']['outcome_p'] = ['Final repair sequence ने यह result दिया:', 'Targeted test suite repair के बाद pass हुई:', 'Targeted tests के बाद एक extra pass ने confirm किया कि fixture-created indexes clean थे। Final audit:'];
$localizedOverrides['hi']['why_p'] = ['Payroll collaboration security-sensitive है। Dashboards, invites, approvals, encryption consent और revoke साफ connection semantics पर निर्भर हैं।', 'नया model PayCal को मजबूत operational boundary देता है:', 'यह separation counts सही रखता है, pending को active access नहीं मानता, terminal states को live indexes से बाहर रखता है, और Redis drift को authority mutate किए बिना repair करने देता है।'];
$localizedOverrides['hi']['not_l'] = ['यह owner-role corruption नहीं था: zero violations।', 'यह revoked या rejected users का leakage नहीं था: terminal buckets zero थे।', 'यह canonical connections rewrite नहीं था: केवल derived indexes reconcile हुए।', 'यह public search policy change नहीं था।'];
$localizedOverrides['hi']['future_l'] = ['Redis snapshot या backup लें।', 'Report चलाएँ और JSON save करें।', '<code>--fix</code> पहले non-production में चलाएँ।', '<code>drift=0</code>, <code>owner_violations=0</code>, <code>other=0</code> confirm करें।', 'Targeted tests चलाएँ।', 'Counts, requests, invites, approvals, revoke और public search smoke-test करें।', 'Production repair केवल quiet window में करें।'];

foreach ($localizedOverrides as $code => $override) {
  $copy[$code] = $override + $copy[$code];
}

$t = $copy[$lang] ?? $copy['de'];

$i18n = [];
foreach (['BREADCRUMB', 'HELP_TOC_TRANSPARENCY_HUB'] as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $t['title'] . ' - [PayCal]';
$pageLabel = $t['title'];
$pageMetaDescription = $t['meta'];
$pageMetaDescriptionLong = $t['meta'];
$pageSocialTitle = $t['title'];
$pageOgDescription = $t['meta'];
$pageTwitterTitle = $t['title'];
$pageTwitterDescription = $t['meta'];
$pageDcTitle = $t['title'];
$pageDcDescription = $t['meta'];
require_once HTML.'/header.php';

function rr_rows(array $rows): void
{
  foreach ($rows as $row) {
    echo '<tr><td><strong>' . $row[0] . '</strong></td><td>' . $row[1] . '</td></tr>' . PHP_EOL;
  }
}

function rr_list(array $items, string $tag = 'ul'): void
{
  echo '<' . $tag . ' class="doc-list">' . PHP_EOL;
  foreach ($items as $item) {
    echo '<li>' . $item . '</li>' . PHP_EOL;
  }
  echo '</' . $tag . '>' . PHP_EOL;
}
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($t['current'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck"><?php echo $t['deck']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-18">2026-06-18</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/redis-connections-reconciliation-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $t['summary']; ?></h2>
      <table class="doc-table" aria-label="Redis connections reconciliation summary"><tbody><?php rr_rows($t['summary_rows']); ?></tbody></table>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['problem']; ?></h2>
      <?php foreach ($t['problem_p'] as $p) { echo '<p>' . $p . '</p>'; } ?>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['model']; ?></h2>
      <p><?php echo $t['model_intro']; ?></p>
      <div class="doc-code-block" data-label="Redis model"><pre><code>Canonical truth:
  business:connection:{businessId}:{userUUID}

Derived active access indexes:
  business:members:{businessId}              active members only
  business:user:{userUUID}                   active businesses only

Derived connection lookup indexes:
  business:connections:{businessId}        active + pending + consented
  business:connections:user:{userUUID}     active + pending + consented

Derived workflow queue:
  business:pending:{businessId}              pending only</code></pre></div>
      <p><?php echo $t['model_after']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['drift']; ?></h2>
      <p><?php echo $t['drift_intro']; ?></p>
      <div class="doc-code-block" data-label="Stale index drift"><pre><code># Derived index says the user belongs to the business
SISMEMBER business:user:user-123 ORGabc
=> 1

# But the canonical lifecycle record no longer exists
HGETALL business:connection:ORGabc:user-123
=> empty

# Correct repair
SREM business:user:user-123 ORGabc</code></pre></div>
      <p><?php echo $t['drift_second']; ?></p>
      <div class="doc-code-block" data-label="Missing active indexes"><pre><code>HGETALL business:connection:ORGabc:user-456
=> status=active role=member

SISMEMBER business:members:ORGabc user-456
=> 0

SISMEMBER business:user:user-456 ORGabc
=> 0

# Correct repair
SADD business:members:ORGabc user-456
SADD business:user:user-456 ORGabc</code></pre></div>
      <p><?php echo $t['drift_after']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['found']; ?></h2>
      <p><?php echo $t['found_intro']; ?></p>
      <table class="doc-table" aria-label="Pre-repair Redis connection audit buckets">
        <thead><tr><th scope="col">Bucket</th><th scope="col">Count</th><th scope="col">Meaning</th></tr></thead>
        <tbody>
          <tr><td><code>stale_index_without_connection</code></td><td>14,886</td><td>Old derived indexes pointed to connections that no longer existed</td></tr>
          <tr><td><code>connection_lookup_missing</code></td><td>2,908</td><td>New non-terminal connection lookup indexes needed backfill</td></tr>
          <tr><td><code>active_missing_member_index</code></td><td>46</td><td>Active connections missing the business-side active member index</td></tr>
          <tr><td><code>active_missing_user_index</code></td><td>493</td><td>Active connections missing the user-side active business index</td></tr>
          <tr><td><code>terminal_in_connection_index</code></td><td>0</td><td>No revoked, rejected, expired, or withdrawn connections leaked into connection lookup indexes</td></tr>
          <tr><td><code>terminal_in_member_index</code></td><td>0</td><td>No terminal connections leaked into active member indexes</td></tr>
          <tr><td><code>owner_violation</code></td><td>0</td><td>Every active business still had a sane owner authority model</td></tr>
          <tr><td><code>other</code></td><td>0</td><td>No unknown drift category was found</td></tr>
        </tbody>
      </table>
      <p><?php echo $t['found_conclusion']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['repair']; ?></h2>
      <p><?php echo $t['repair_p'][0]; ?></p>
      <div class="doc-code-block" data-label="Internal ops command"><pre><code>scripts/paycal business:connections:audit</code></pre></div>
      <p><?php echo $t['repair_p'][1]; ?></p>
      <?php rr_list($t['modes']); ?>
      <p><?php echo $t['repair_p'][2]; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['operators']; ?></h2>
      <div class="operator-command-list">
        <?php
        $commands = [
          ['Report-only', 'scripts/paycal business:connections:audit --json'],
          ['Scoped report', 'scripts/paycal business:connections:audit --business ORGabc123 --json'],
          ['Before artifact', "scripts/paycal business:connections:audit --json \\\n  &gt; tmp/connections-report-before.json"],
          ['Controlled repair', "scripts/paycal business:connections:audit --fix --json \\\n  &gt; tmp/connections-fix.json"],
          ['After artifact', "scripts/paycal business:connections:audit --json \\\n  &gt; tmp/connections-report-after.json"],
          ['Clean result', "drift=0\nowner_violations=0\nother=0"],
        ];
        foreach ($commands as $idx => $cmd) {
          echo '<div class="subject-example-cutout operator-command"><p>' . $t['operator_labels'][$idx] . '</p><div class="doc-code-block" data-label="' . $cmd[0] . '"><pre><code>' . $cmd[1] . '</code></pre></div></div>';
        }
        ?>
      </div>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['exit']; ?></h2>
      <p><?php echo $t['exit_intro']; ?></p>
      <table class="doc-table" aria-label="Connection audit exit codes">
        <thead><tr><th scope="col">Exit code</th><th scope="col">Meaning</th><th scope="col">Operational interpretation</th></tr></thead>
        <tbody>
          <tr><td><code>0</code></td><td>Clean audit, or <code>--fix</code> repaired known repairable drift</td><td>Safe to proceed</td></tr>
          <tr><td><code>1</code></td><td>Report-only drift found</td><td>Known repairable drift exists; review or run controlled repair</td></tr>
          <tr><td><code>2</code></td><td>Owner invariant violation</td><td>Stop and review manually; never auto-pick an owner</td></tr>
          <tr><td><code>3</code></td><td>Unknown or other drift found</td><td>Stop and classify the new drift type before repairing</td></tr>
          <tr><td><code>4</code></td><td>Script or Redis failure</td><td>Infrastructure failure; rerun only after root cause is clear</td></tr>
        </tbody>
      </table>
      <p><?php echo $t['exit_after']; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['actions']; ?></h2>
      <?php rr_list($t['actions_l'], 'ol'); ?>
    </section>

    <section class="doc-section success">
      <h2><?php echo $t['outcome']; ?></h2>
      <p><?php echo $t['outcome_p'][0]; ?></p>
      <div class="doc-code-block" data-label="Repair sequence"><pre><code>Before:
drift=18349 owner=0 other=0

Fix:
fixed=18349 exit=0

After:
drift=0 owner=0 other=0</code></pre></div>
      <p><?php echo $t['outcome_p'][1]; ?></p>
      <div class="doc-code-block" data-label="Test result"><pre><code>83 tests, 578 assertions
OK</code></pre></div>
      <p><?php echo $t['outcome_p'][2]; ?></p>
      <div class="doc-code-block" data-label="Final audit"><pre><code>after_exit=0
connections=1488
businesses=1237
drift=0
owner=0
other=0</code></pre></div>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['why']; ?></h2>
      <p><?php echo $t['why_p'][0]; ?></p>
      <p><?php echo $t['why_p'][1]; ?></p>
      <div class="doc-code-block" data-label="Design principle"><pre><code>connection = lifecycle record
membership   = active access state
pending      = workflow state
indexes      = disposable projections</code></pre></div>
      <p><?php echo $t['why_p'][2]; ?></p>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['not']; ?></h2>
      <?php rr_list($t['not_l']); ?>
    </section>

    <section class="doc-section">
      <h2><?php echo $t['future']; ?></h2>
      <?php rr_list($t['future_l'], 'ol'); ?>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
