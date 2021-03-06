<?php

include_spip('idm');

function formulaires_forum_relecture_charger ($id_article, $id_parent) {
  $id_article = intval ($id_article);
  $valeurs = array('titre'      => sql_getfetsel ('titre', 'spip_articles', "id_article = $id_article"),
                   'texte'      => '',
                   'id_article' => $id_article,
                   'id_parent'  => $id_parent);
  return $valeurs;
}

function formulaires_forum_relecture_verifier ($id_article, $id_parent) {
  $erreurs = array();

  foreach(array('titre','texte') as $o)
    if (!_request($o)) $erreurs[$o] = "Le champ '$o' est obligatoire !";

  if ((count($erreurs)==0) && (!_request("ok"))) $erreurs["preview"] = "preview";

  return $erreurs;
}

function formulaires_forum_relecture_traiter ($id_article, $id_parent) {
  $id_auteur = intval ($GLOBALS['auteur_session']['id_auteur']);
  $auteur    = sql_getfetsel ('nom', 'spip_auteurs', "id_auteur = $id_auteur");

  $id_forum = sql_insertq ('spip_forum', array (
    'id_parent'  => intval ($id_parent),
    'objet'      => 'article',
    'id_objet'   => intval ($id_article),
    'id_auteur'  => $id_auteur,
    'auteur'     => $auteur,
    'titre'      => _request ('titre'),
    'texte'      => _request ('texte'),
    'date_heure' => 'NOW()',
    'statut'     => 'relmod'
  ));

  sql_insertq ('spip_idm_relecture', array(
    'id_auteur'  => $id_auteur,
    'action'     => 'comment',
    'id_article' => intval($id_article),
    'id_forum'   => intval($id_forum)
  ));

  if (sql_countsel ('spip_forum', "id_auteur=$id_auteur AND id_article=$id_article") == 1) {
    $comments = intval ( sql_getfetsel ('comments', 'spip_idm_relecteurs', "id_auteur=$id_auteur") );
    sql_updateq ('spip_idm_relecteurs', array('comments'=>$comments + 1), "id_auteur=$id_auteur");
  }
  
  $subject = "Un nouveau message de relecture soumis sur IdM";
  $message = _T('idm:mail_rel_forum_soumis');

  idm_notify ('relecture', $message, $subject);

  return array ('message_ok' => "done");
}

?>
