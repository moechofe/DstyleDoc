<?php

chdir('../');

require_once( 'xdebug.front.end.php' );

require_once( 'DstyleDoc.php' );
require_once( 'converter.FirstStyle.php' );

set_time_limit( 90 );
error_reporting( E_ALL | E_STRICT );

DstyleDoc::hie()
  ->source( 'DstyleDoc.php' )
  ->convert_with( 

    DstyleDoc_Converter_FirstStyle::hie()
      ->template_dir( 'converter.FirstStyle' )
      ->config( array(
        'skin' => 'rosy',
        'logo' => 'DstyleDoc API documentation',

        'page_class' => 'Cette page traite de la classe %2$s d�clar�e dans le fichier %4$s. <a href="#page-browser">Acc�dez � la navigation</a>.',
        'page_method' => 'Cette page traite de la m�thode %2$s de la classe %4$s d�clar�e dans le fichier %6$s. <a href="#page-browser">Acc�dez � la navigation</a>.',

        'files_index_list_header' => 'Liste des fichiers',
        'classes_index_list_header' => 'Liste des classes d�clar�es dans le fichier <span class="file">%s</span>',
        'methods_index_list_header' => 'Liste des m�thodes de la classe <span class="class">%s</span>',
      ) )
  
  );

?>
