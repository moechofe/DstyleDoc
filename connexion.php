<?php

/**
 * Couche d'abstraction simple pour mysql.
 *
 * @package connexion
 * @TODO faire en sorte que les requetes accept le parametre ! en plus mais pas \!
 */

/**
 * Couche d'abstraction simple pour requ�te vers serveur mysql.
 *
 * Utilise les extensions mysql ou mysqli si cette derni�re est disponible pour effectuer
 * les requ�tes.
 *
 * @package connexion
 */
class mysql_connexion
{
  // {{{ $mailto

  /**
   * Liste de adresse de destination des alertes email.
   * @var string
   */
  static public $mailto = 'mm@orbus.fr';

  // }}}
  // {{{ $driver

  /**
   * Le nom de la classe du driver utilis�.
   * @var string
   */
  static public $driver = null;

  // }}}
  // {{{ get_driver()

  static public function get_driver( $host, $user, $pass, $base )
  {
    if( is_null( self::$driver ) )
    {
      if( isset($_REQUEST['enable_pdo']) and extension_loaded('pdo') and in_array( 'mysql', PDO::getAvailableDrivers() ) )
        self::$driver = 'mysql_connexion_pdo';
      if( extension_loaded('mysqli') )
        self::$driver = 'mysql_connexion_mysqli';
      elseif( extension_loaded('mysql') )
        self::$driver = 'mysql_connexion_mysql';
      if( is_null( self::$driver ) )
        throw new mysql_connexion_no_driver;
    }

    return new self::$driver( $host, $user, $pass, $base );
  }

  // }}}
}

// {{{ mysql_connexion_no_driver

/**
 * Exception lanc� si aucun driver n'est disponible pour ouvrir la connexion.
 * @package connexion
 * @subpackage exception
 */
class mysql_connexion_no_driver extends RuntimeException
{
  public function __construct( $message )
  {
    parent::__construct( 'N\'a pas pu trouv� de driver pour la connexion � mysql.' );
  }
}

// }}}
// {{{ mysql_connexion_connection_error

/**
 * Exception lanc� si la connexion vers le serveur de base de donn�e
 * @package connexion
 * @subpackage exception
 */
class mysql_connexion_connect_error extends Exception
{
  public function __construct( $message )
  {
    parent::__construct( 'N\'a pas pu se connecter : '.$message );
  }
}

// }}}
// {{{ mysql_connexion_query

/**
 * Exception lanc� si une requ�te vers la base de donn�e � provoqu� une erreur
 * @package connexion
 * @subpackage exception
 */
class mysql_connexion_query extends Exception
{
  public function __construct( $query, $message )
  {
    parent::__construct( 'Erreur avec la requ&ecirc;te : '.$query." \n".$message );
  }
}

// }}}
// {{{ mysql_connexion_argument

/**
 * Exception lanc� si les arguments pass� au script sont mauvais
 * @package connexion
 * @subpackage exception
 */
class mysql_connexion_argument extends InvalidArgumentException
{
  public function __construct( $method )
  {
    parent::__construct( 'Les arguments pass�s � la m�thode : '.(string)$method.' ne sont pas correct.' );
  }
}

// }}}
// {{{ mysql_connexion_bad_driver

/**
 * Exception lanc� si le driver n'est pas conforme.
 * @package connexion
 * @subpackage exception
 */
class mysql_connexion_bad_driver extends RuntimeException
{
  public function __construct( $driver )
  {
    parent::__construct( 'Le driver '.(string)$driver.' n\'est pas un driver valide.' );
  }
}

// }}}

/**
 * Classe de driver.
 *
 * Permet � partir d'une unique m�thode d'envoyer des requ�tes et de traiter les r�sultats.
 * En utilisant les jokers "?" et "!", les param�tres pass�s � la fonction seront ajouter dans la requ�te � l'endroit appropri�.
 * Avec le joker "?", les param�tres seront echap� si ils sagit de cha�ne de caract�res.
 * Le joker "!" permet de ne jamais �chaper les param�tres.
 *
 * <code>
 * <?php
 *   $mysql->query( 'SELECT 1' );
 * ?>
 * </code>
 *
 * <code>
 * <?php
 *   $mysql->query( 'SELECT * FROM table WHERE id = ?', (integer)$_GET['id'] );
 * ?>
 * </code>
 *
 * <code>
 * <?php
 *   $mysql->query( 'INSERT !table (name) VALUES(?),(?)', $prefix, (string)$value[1], (string)$value[2] );
 * ?>
 * </code>
 *
 * R�cup�rer les r�sultats :
 *
 * <code>
 * <?php
 *   $last_insert_id = $mysql->query( 'INSERT...' ... );
 * ?>
 * </code>
 *
 * <code>
 * <?php
 *   $data = $mysql->query( 'SELECT * FROM table' );
 * ?>
 * </code>
 *
 * Changer le mode de r�cup�ration des r�sultats :
 *
 * <code>
 * <?php
 *   $mysql->row->query( 'SELECT * FROM table LIMIT 1' );
 * ?>
 * </code>
 *
 * <code>
 * <?php
 *   $mysql->asso->multi->query( 'SELECT id, name FROM table' );
 * ?>
 * </code>
 *
 * <code>
 * <?php
 *   while( $row = $mysql->one->query( 'SELECT * FROM table' ) )
 *   null;
 * ?>
 * </code>
 */
abstract class mysql_connexion_driver
{
  // {{{ __get()

  public function __get( $property )
  {
    switch( $property )
    {
    case 'asso':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::numerical_key );
      break;

    case 'num':
      $this->fetch_mode |= self::numerical_key;
      break;

    case 'row':
      $this->fetch_mode |= self::one_row;
      break;

    case 'multi':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::one_row );
      break;

    case 'one':
      $this->fetch_mode |= self::one_by_one;
      break;

    case 'all':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::one_by_one );
      break;

    case 'key':
      $this->fetch_mode |= self::first_field_for_key;
      break;

    case 'inc':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::first_field_for_key );
      break;

    case 'col':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::one_row );
      $this->fetch_mode |= self::one_field;
      break;

    case 'field':
      $this->fetch_mode |= self::one_field;
      break;

    case 'fields':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::one_field );
      break;

    case 'keep':
      $this->fetch_mode |= self::keep_open;
      break;

    case 'close':
      $this->fetch_mode = ~( ~$this->fetch_mode | self::keep_open );
      break;

    case 'save':
      $this->set_fetch_mode();
      break;

    }

    return $this;
  }

  // }}}
  // {{{ query()

  /**
   * Execute une requete et retourne les resultats ou last_insert_id
   * Se connect au serveur, contruit la requ�te avec les arguments, execute la requ�te, retourne les r�sultat.
   *
   * Deux syntaxes sont possible :
   * <code>
   *   self::query( $statement );
   *   self::query( $prepare, $arg1, $arg2, ... );
   * </code>
   *
   * La deuxi�me syntaxe s'assure d'echapper les chaines de caract�re des param�tres et de les entourer par des guillemets. Pour cela, la requ�te doit contenir des '?' qui seront remplac�s par les valeurs des arguments.
   *
   * Effectue une requ�te sans r�sultat :
   * <code>
   * <?php
   *   self::query( 'INSERT table SET field = ?', (integer) $value );
   *   // executera <pre>INSERT table SET field = 3</pre>
   *
   *   self::query( 'INSERT table SET field = ?', (string) $value );
   *   // executera <pre>INSERT table SET field = '3'</pre>
   * ?>
   * </code>
   *
   * Si une requ�te de type insert ou update est envoy�, le last_insert_id sera retourn�. Si aucun id � �t� modifier, true sera retourn�.
   *
   * @param string La requ�te avec des "?"
   * @param mixed,... Les param�tres a binder avec les "?" de la requ�te.
   * @return array,boolean,string,integer
   */
  abstract public function query();

  // }}}
  // {{{ $defaut_fetch_mode

  /**
   * Sauvegarde le fetch_mode par d�faut.
   *
   * @var integer
   */
  protected $defaut_fetch_mode = 0x0;

  // }}}
  // {{{ set_fetch_mode()

  /**
   * Change le mode de recup�ration des r�sultats par d�faut.
   *
   * @param integer
   * @return mysql_connexion_driver
   */
  public function set_fetch_mode( $mode = null )
  {
    if( ! is_null($mode) )
      $this->fetch_mode = (integer) $mode;
    $this->defaut_fetch_mode = $this->fetch_mode;
    return $this;
  }

  // }}}
  // {{{ get_fetch_mode()

  /**
   * Renvoie le mode de r�cup�ration des r�sultats par d�fault.
   */
  public function get_fetch_mode()
  {
    return $this->defaut_fetch_mode;
  }

  // }}}
  // {{{ $fetch_mode

  /**
   * Le mode de fetch utilis�.
   * @var integer
   */
  protected $fetch_mode = 0x0;

  // }}}
  // {{{ numerical_key

  /**
   * Retourne des clefs numeric plutot que des clefs associative.
   *
   * @var integer
   */
  const numerical_key = 0x1;

  // }}}
  // {{{ one_row

  /**
   * Retourne une seul ligne de r�sultat.
   * @var integer
   */
  const one_row = 0x2;

  // }}}
  // {{{ one_by_one

  /**
   * Indique de ne pas fetcher les donn�es.
   *
   * Permet de r�cup�rer les lignes une par une.
   * @var integer
   */
  const one_by_one = 0x4;

  // }}}
  // {{{ first_field_for_key

  /**
   * Indique de prendre la valeur du premier champ de chaque ligne et de l'utiliser en temps que clef du tableau repr�sentant les donn�es de chaques lignes du r�sutat.
   *
   * @var integer
   */
  const first_field_for_key = 0x8;

  // }}}
  // {{{ keep_open

  /**
   * Indique de ne pas refermer la connexion � chaque requ�te. Pratique pour les transactions.
   *
   * @var integer
   */
  const keep_open = 0x10;

  // }}}
  // {{{ one_field

  /**
   * Retourne la premi�re colonne de la premi�re ligne de r�sultat.
   * @var integer
   */
  const one_field = 0x20;

  // }}}
  // {{{ $host

  /**
   * Le host du serveur mysql
   * @var string
   */
  protected $host = null;

  // }}}
  // {{{ $user

  /**
   * L'utilisateur mysql
   * @var string
   */
  protected $user = null;

  // }}}
  // {{{ $pass

  /**
   * Le mot de passe de l'utilisateur mysql
   * @var string
   */
  protected $pass = null;

  // }}}
  // {{{ $base

  /**
   * Le nom de la base de donn�e
   * @var string
   */
  protected $base = null;

  // }}}
  // {{{ __construct()

  public function __construct( $host, $user, $pass, $base )
  {
    $this->host = $host;
    $this->user = $user;
    $this->pass = $pass;
    $this->base = $base;
  }

  // }}}
  // {{{ $query_attempt

  /**
   * Indique combien d'essaye de requ�tes sont effectu�es avant de retourner une erreur.
   *
   * @var integer
   */
  protected $query_attempt = 2;

  // }}}
  // {{{ $query_time_wait

  /**
   * indique combien de microsecond il est nescessaire d'attendre avant d'effectuer un autre essaye de requ�te.
   *
   * @var integer
   */
  protected $query_time_wait = 100;

  // }}}
  // {{{ fill()

  protected function fill( &$data, $row, $mode )
  {
    if( $mode & self::one_field and $mode & self::one_row )
      $data = array_shift($row);
    elseif( $mode & self::one_row )
      $data = $row;
    elseif( $mode & self::one_field and $mode & self::first_field_for_key )
    {
      list($key) = array_slice(array_values($row),0,1);
      $data[$key] = $key;
    }
    elseif( $mode & self::first_field_for_key )
    {
      list($key) = array_slice(array_values($row),0,1);
      $data[$key] = $row;
    }
    elseif( $mode & self::one_field )
      $data[] = array_shift($row);
    else
      $data[] = $row;

    if( $mode & self::one_by_one )
      return false;
    else
      return true;
  }

  // }}}
}

/**
 * Driver de connexion pour mysqli.
 * @package connexion
 * @subpackage driver
 */
class mysql_connexion_mysqli extends mysql_connexion_driver
{
  // {{{ $result

  /**
   * L'identifiant du jeu de r�sultat
   *
   * @var integer
   */
  protected $result = null;

  // }}}
  // {{{ bind_param()

  /**
   * Assigne un ? de la requete au param�tre suivant dans la liste.
   * @param array La valeur trouv� par preg_replace()
   * @param array La liste des param�tres
   * @param resource Un lien de connection mysql
   * @return string,numeric Le param�tre suivant dans la liste
   */
  protected function bind_param( $match, &$args, $link )
  {
    if( count($args) < 1 )
      return 'NULL';
    elseif( is_numeric( $arg = array_shift($args) ) )
      return $arg;
    elseif( is_null($arg) )
      return 'NULL';
    elseif( $match == '!' )
      return $arg;
    else
      return '\''.mysqli_escape_string($link,(string)$arg).'\'';
  }

  // }}}
  // {{{ query()

  public function query()
  {
    static $attempt = 1;

    if( ! $this->result )
    {
      $args = func_get_args();
      if( count($args) < 1 )
        throw new mysql_connexion_argument( __FUNCTION__ );
      $query = array_shift( $args );

      if( ! $link = @mysqli_connect( $this->host, $this->user, $this->pass, $this->base ) )
        throw new mysql_connexion_connect_error(mysqli_connect_error());

      $query = preg_replace( '/((?<!\w)\?(?!\w)|(?<!\w)!(?!\w)|\b!(?!\w)|(?<!\w)!\b)/e', "self::bind_param('\\1', \$args, \$link)", $query );

      $this->result = @mysqli_query($link, $query);
    }

    $data = false;

    if( $this->result )
    {
      $data = array();
      if( is_bool($this->result) )
      {
        if( ! $data = mysqli_insert_id($link) )
          $data = true;
      }
      elseif( $this->fetch_mode & self::numerical_key )
      {
        while( $row = mysqli_fetch_row($this->result) )
          if( ! $this->fill( $data, $row, $this->fetch_mode ) )
            break;
      }
      else
      {
        while( $row = mysqli_fetch_assoc($this->result) )
          if( ! $this->fill( $data, $row, $this->fetch_mode ) )
            break;
      }
    }
    elseif( ++$attempt > (integer)$this->query_attempt )
    {
      $attempt = 1;
      throw new mysql_connexion_query($query, mysqli_error($link));
    }
    else
    {
      usleep( (integer)$this->query_time_wait );
      $args = func_get_args();
      return call_user_func_array( array($this,'query'), $args );
    }

    if( $this->fetch_mode & self::one_by_one )
      return $data;
    elseif( $this->fetch_mode & self::keep_open )
    {
      $this->result = null;
      return $data;
    }

    mysqli_close($link);
    $this->result = null;
    $this->fetch_mode = $this->defaut_fetch_mode;

    return $data;
  }

  // }}}
}

/**
 * Driver de connexion pour mysql
 * @package connexion
 * @subpackage driver
 */
class mysql_connexion_mysql extends mysql_connexion_driver
{
  // {{{ $resource

  /**
   * Une r�f�rence vers la ressource mysql
   *
   * @var resource
   */
  protected $resource = null;

  // }}}
  // {{{ bind_param()

  /**
   * Assigne un ? de la requete au param�tre suivant dans la liste.
   * @param array La valeur trouv� par preg_replace()
   * @param array La liste des param�tres
   * @param resource Un lien de connection mysql
   * @return string,numeric Le param�tre suivant dans la liste
   */
  static protected function bind_param( $match, &$args, $link )
  {
    if( count($args) < 1 )
      return 'NULL';
    elseif( is_numeric( $arg = array_shift($args) ) )
      return $arg;
    elseif( is_null($arg) )
      return 'NULL';
    elseif( $match == '!' )
      return $arg;
    else
      return '\''.mysql_real_escape_string((string)$arg, $link).'\'';
  }

  // }}}
  // {{{ query()

  public function query()
  {
    static $attempt = 1;

    if( ! $this->resource )
    {

      $args = func_get_args();
      if( count($args) < 1 )
        throw new mysql_connexion_argument( __FUNCTION__ );
      $query = array_shift( $args );

      if( ! $link = @mysql_connect( $this->host, $this->user, $this->pass ) )
        throw new mysql_connexion_connect_error(mysql_error());
      if( ! @mysql_select_db( $this->base, $link ) )
        throw new mysql_connexion_connect_error(mysql_error());

      $query = preg_replace( '/((?<!\w)\?(?!\w)|(?<!\w)!(?!\w)|\b!(?!\w)|(?<!\w)!\b)/e', "self::bind_param('\\1', \$args, \$link)", $query );

      $this->resource = @mysql_query($query, $link);

    }

    $data = false;

    if( $this->resource )
    {
      $data = array();
      if( is_bool($this->resource) )
      {
        if( ! $data = @mysql_insert_id($link) )
          $data = true;
      }
      elseif( $this->fetch_mode & self::numerical_key )
      {
        while( $row = mysql_fetch_row($this->resource) )
          if( ! $this->fill( $data, $row, $this->fetch_mode ) )
            break;
      }
      else
      {
        while( $row = mysql_fetch_assoc($this->resource) )
          if( ! $this->fill( $data, $row, $this->fetch_mode ) )
            break;
      }
    }
    elseif( ++$attempt > (integer)$this->query_attempt )
    {
      $attempt = 1;
      throw new mysql_connexion_query($query, mysql_error($link));
    }
    else
    {
      usleep( (integer)$this->query_time_wait );
      $args = func_get_args();
      return call_user_func_array( array($this,'query'), $args );
    }

    if( $this->fetch_mode & self::one_by_one )
      return $data;
    elseif( $this->fetch_mode & self::keep_open )
    {
      $this->result = null;
      return $data;
    }

    mysql_close($link);
    $this->resource = null;
    $this->fetch_mode = $this->defaut_fetch_mode;

    return $data;
  }

  // }}}
}

/**
 * Driver de connexion pour pdo
 * @package connexion
 * @subpackage driver
 */
class mysql_connexion_pdo extends mysql_connexion_driver
{
  // {{{ $result

  /**
   * Une r�f�rence vers l'obejct des r�sultats PDO.
   *
   * @var PDOStatement
   */
  protected $result = null;

  // }}}
  // {{{ query()

  public function query()
  {
    static $attempt = 1;

    try
    {

    if( ! $this->resource )
    {

      $args = func_get_args();
      if( count($args) < 1 )
        throw new mysql_connexion_argument( __FUNCTION__ );
      $query = array_shift( $args );

      $link = new PDO( 'mysql:dbname='.$this->base.';host='+$this->host, $this->user, $this->pass );
      $link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );

      if( $this->result = $link->prepare($query) )
        $this->result->execute($args);

    }

    $data = false;

    if( $this->result )
    {
      $data = array();
      if( is_bool($this->result) )
      {
        if(  ! $data = $link->lastInsertId() )
          $data = true;
      }
      elseif( $this->fetch_mode & self::numerical_key )
      {
        while( $row = $this->result->fetch(PDO::FETCH_NUM) )
          if( ! $this->fill( $data, $row, $this->fetch_mode ) )
            break;
      }
      else
      {
        while( $row = $this->result->fetch(PDO::FETCH_ASSOC) )
          if( ! $this->fill( $data, $row, $this->fetch_mode ) )
            break;
      }
    }
    elseif( ++$attempt > (integer)$this->query_attempt )
    {
      $attempt = 1;
      throw new mysql_connexion_query($query, join("\n",$link->errorInfo));
    }
    else
    {
      usleep( (integer)$this->query_time_wait );
      $args = func_get_args();
      return call_user_func_array( array($this,'query'), $args );
    }

    if( $this->fetch_mode & self::one_by_one )
      return $data;
    elseif( $this->fetch_mode & self::keep_open )
    {
      $this->result = null;
      return $data;
    }

    mysql_close($link);
    $this->result = null;
    $this->fetch_mode = $this->defaut_fetch_mode;

    return $data;

    }
    catch( PDOException $e )
    {
      throw new mysql_connexion_connect_error($e->getMessage());
    }

  }

  // }}}
}

?>
