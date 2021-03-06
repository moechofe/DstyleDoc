<?php
namespace dstyledoc\converters;

require_once 'converter.HTML.php';

/**
 * Convertisseur qui affiche du HTML.
 */
class ConverterSimple extends ConverterHTML
{
	// {{{ convert_file()

	public function convert_file( ElementFile $file )
	{
		if( isset($_REQUEST['debug']) and strpos($_REQUEST['debug'],'log')!==false )
			DstyleDoc::log( "<span style=\"color: RoyalBlue\">Convert file: <strong>{$file->display}</strong></span>", true );

		return <<<HTML
<hr /><h1 id="{$file->id}">File: {$file->display}</h1>
<dl>
{$this->either($file->title,'<dt>title</dt><dd>'.$file->title.'</dd>')}
{$this->either($file->description,'<dt>description</dt><dd>'.$file->description.'</dd>')}
{$this->either($file->version,'<dt>version</dt><dd>'.$file->version.'</dd>')}
{$this->either($file->historys,'<dt>history</dt><dd>'.$this->forall($file->historys,'<li><b>{$value->version}: </b>{$value->description}</li>').'</dd>')}
{$this->either($file->packages,'<dt>package</dt><dd>'.$this->forall($file->packages,'<li>{$value}</li>').'</dd>')}
{$this->either($file->classes,'<dt>classes</dt><dd><ul>'.$this->forall($file->classes,'<li>$value->link</li>').'</ul></dd>')}
{$this->either($file->interfaces,'<dt>interfaces</dt><dd><ul>'.$this->forall($file->interfaces,'<li>$value->link</li>').'</ul></dd>')}
{$this->either($file->functions,'<dt>functions</dt><dd><ul>'.$this->forall($file->functions,'<li>$value->link</li>').'</ul></dd>')}
</dl>
HTML;
	}

	// }}}
	// {{{ convert_class()

	public function convert_class( DstyleDoc_Element_Class $class )
	{
		if( isset($_REQUEST['debug']) and strpos($_REQUEST['debug'],'log')!==false )
			DstyleDoc::log( "<span style=\"color: RoyalBlue\">Convert class: <strong>{$class->display}</strong></span>", true );

		if( is_object($class->parent) )
			$super = $class->parent->link;
		elseif( $class->parent )
			$super = $class->parent;
		else
			$super = null;

		return <<<HTML
<hr /><h1 id="{$class->id}">{$this->either($class->final,'Final ','')}{$this->either($class->abstract,'Abstract ','')}Class: {$class->display}</h1>
<dl>
{$this->element_filed($class)}
{$this->either($class->parent,"<dt>super class</dt><dd>$super</dd>")}
{$this->either($class->implements,'<dt>implement</dt><dd><ul>'.$this->forall($class->implements,'<li>{$value->link}</li>').'</ul></dd>')}
<dt>methods</dt>
<dd>
	<ul>
		{$this->forall($class->methods,'<li>$value->link $value->title</li>')}
	</ul>
</dd>
<dt>members</dt>
<dd>
	<ul>
		{$this->forall($class->members,'<li>$value->link $value->title</li>')}
	</ul>
</dd>
<dd>
	{$this->forall($class->methods,'$value')}
	{$this->forall($class->members,'$value')}
</dd>
</dl>
HTML;
	}

	// }}}
	// {{{ convert_interface()

	public function convert_interface( DstyleDoc_Element_Interface $interface )
	{
		if( isset($_REQUEST['debug']) and strpos($_REQUEST['debug'],'log')!==false )
			DstyleDoc::log( "<span style=\"color: RoyalBlue\">Convert interface: <strong>{$interface->display}</strong></span>", true );

		return <<<HTML
<hr /><h1 id="{$interface->id}">Interface: {$interface->display}</h1>
<dl>
{$this->element_filed($interface)}
<dt>methods</dt>
<dd>
	<ul>
		{$this->forall($interface->methods,'<li>$value->link</li>')}
	</ul>
	{$this->forall($interface->methods,'$value')}
</dd>
</dl>
HTML;
	}

	// }}}
	// {{{ convert_function()

	public function convert_function( DstyleDoc_Element_Function $function )
	{
		if( isset($_REQUEST['debug']) and strpos($_REQUEST['debug'],'log')!==false )
			DstyleDoc::log( "<span style=\"color: RoyalBlue\">Convert function: <strong>{$function->display}</strong></span>", true );

		return <<<HTML
<hr /><h1 id="{$function->id}">Function: {$function->display}</h1>
<dl>
{$this->element_filed($function)}
<dt>syntax</dt><dd>{$this->forall($function->syntaxs,'<ul>$value</ul>')}</dd>
<dt>params</dt><dd><ul>{$this->forall($function->params,'<li>$value</li>')}</ul></dd>
{$this->either($function->returns,
'<dt>returns</dt><dd><ul>'.$this->forall($function->returns,'<li>$value</li>').'</ul></dd>')}
{$this->either($function->exceptions,
'<dt>exceptions</dt><dd><ul>'.$this->forall($function->exceptions,'<li>$value</li>').'</ul></dd>')}
</dl>
HTML;
	}

	// }}}
	// {{{ convert_method()

	public function convert_method( DstyleDoc_Element_Method $method )
	{
		if( isset($_REQUEST['debug']) and strpos($_REQUEST['debug'],'log')!==false )
			DstyleDoc::log( "<span style=\"color: RoyalBlue\">Convert method: <strong>{$method->display}</strong></span>", true );

		return <<<HTML
<hr /><h1 id="{$method->id}">method: {$method->display}</h1>
<dl>
{$this->element_filed($method)}
<dt>class</dt><dd>{$method->class->link} {$method->class->title}</dd>
<dt>syntax</dt>{$this->forall($method->syntaxs,'<dd>$value</dd>')}
<dt>params</dt><dd><ul>{$this->forall($method->params,'<li>$value</li>')}</ul></dd>
{$this->either($method->returns,
'<dt>returns</dt><dd><ul>'.$this->forall($method->returns,'<li>$value</li>').'</ul></dd>')}
{$this->either($method->exceptions,
'<dt>exceptions</dt><dd><ul>'.$this->forall($method->exceptions,'<li>$value</li>').'</ul></dd>')}
</dl>
HTML;
	}

	// }}}
	// {{{ convert_syntax()

	public function convert_syntax( DstyleDoc_Element_Syntax $syntax )
	{
		$params = '';
		foreach( $syntax->params as $param )
		{
			$params .= ', '.
				(($param->optional)?'[ ':'').
				(($param->type)?'<i>'.$param->type.'</i> ':'').
				"\${$param->var}".
				(($param->optional)?' ]':'');
		}
		$params = substr($params,2);

		$returns = '';
		foreach( $syntax->returns as $return )
		{
			$type = $return->type;
			if( $type instanceof DstyleDoc_Element )
				$returns .= ", <i>{$type->link}</i>";
			else
				$returns .= ", <i>{$type}</i>";
		}
		$returns = substr($returns,2);
//		if( $returns ) $returns .= ' = ';

		$call = substr($syntax->function->display,0,-1);

		return <<<HTML
<li><span class="syntax">{$returns} {$call} {$params} )</span> = {$syntax->description}</li>
HTML;
	}

	// }}}
	// {{{ convert_param()

	public function convert_param( DstyleDoc_Element_Param $param )
	{
		$types = implode(', ', $param->types);
		return <<<HTML
{$this->either($param->types,'<i>('.$types.')</i> ') }\${$param->var} = {$this->either($param->default,'<i>\['.$param->default.'\]</i> ')}{$param->description}
HTML;
	}

	// }}}
	// {{{ convert_type()

	public function convert_type( DstyleDoc_Element_Type $type )
	{
		$types = $type->type;
		if( $types instanceof DstyleDoc_Element )
			return <<<HTML
{$types->link}
HTML;
		/*
			return <<<HTML
{$types->link}: {$type->description}
HTML;
		 */
		else
			return <<<HTML
{$types}
HTML;
		/*
			return <<<HTML
{$types}: {$type->description}
HTML;
			 */
	}

	// }}}
	// {{{ convert_return()

	public function convert_return( DstyleDoc_Element_Return $return )
	{
		$types = $return->type;
		if( $types instanceof DstyleDoc_Element )
			return <<<HTML
{$types->link}: {$return->description}
HTML;

		else
			return <<<HTML
<i>{$types}</i> = {$return->description}
HTML;
	}

	// }}}
	// {{{ convert_exception()

	public function convert_exception( DstyleDoc_Element_Exception $exception )
	{
		return <<<HTML
{$exception->name}: {$exception->description}
HTML;
	}

	// }}}
	// {{{ convert_member()

	public function convert_member( DstyleDoc_Element_Member $member )
	{
		$type = $member->types;

		$return = <<<HTML
<hr /><h1 id="{$member->id}">member: {$member->display}</h1>
<dt>description</dt>
<dd>{$member->title}</dd>
HTML;

		if( is_array($type) and count($type) )
			return <<<HTML
{$return}
<dt>types</dt>
<dd>
	<ul>{$this->forall($type,'<li>$value</li>')}</ul>
</dd>
HTML;
		else
			trigger_error('fixme');

		// fixme: ça sert a quoi ?
		/*
		elseif( $type instanceof DstyleDoc_Element )
			return <<<HTML
{$return}
<dt>types</dt>
<dd>
	{$member->type}: {$member->description}
</dd>
HTML;

		else
			return <<<HTML
{$return}
{$type}: {$member->description}
HTML;*/
	}

	// }}}
	// {{{ convert_link()

	public function convert_link( $id, $name, Element $element )
	{
		return <<<HTML
<a href="#{$id}">{$name}</a>
HTML;
	}

	// }}}
	// {{{ convert_all()

	public function convert_all()
	{
		echo <<<HTML
<style>
dl dt { margin-top: 0px; font-weight: bold; }
dl dd { margin-left: 20px; }
</style>
HTML;

		$this->index_files();
		$this->index_functions();
		$this->index_interfaces();
		$this->index_classes();

		$this->all_files();
		$this->all_functions();
		$this->all_interfaces();
		$this->all_classes();
	}

	// }}}

	// {{{ forall()

	protected function forall( $var, $eval )
	{
		$result = '';
		foreach( $var as $key => $value )
			$result .= eval('return "'.$eval.'";');
		return $result;
	}

	// }}}
	// {{{ either()

	protected function either( $if = false, $then = null, $else = null )
	{
		if( $if )
		{
			if( is_string($then) )
				return eval('return stripslashes(\''.addslashes($then).'\');');
		}
		else
			if( is_string($else) )
				return eval('return stripslashes(\''.addslashes($else).'\');');
	}

	// }}}
	// {{{ all_files()

	protected function all_files()
	{
		foreach( $this->files as $file )
			echo $file;
	}

	// }}}
	// {{{ all_functions()

	protected function all_functions()
	{
		foreach( $this->functions as $function )
			echo $function;
	}

	// }}}
	// {{{ all_interfaces()

	protected function all_interfaces()
	{
		foreach( $this->interfaces as $interface )
			echo $interface;
	}

	// }}}
	// {{{ all_classes()

	protected function all_classes()
	{
		foreach( $this->classes as $class )
			echo $class;
	}

	// }}}
	// {{{ index_files()

	protected function index_files()
	{
		echo <<<HTML
<hr /><h1>Files index</h1>
<ul>
	{$this->forall($this->files,'<li>$value->link</li>')}
</ul>
HTML;
	}

	// }}}
	// {{{ index_functions()

	protected function index_functions()
	{
		if( ! $this->functions ) return null;
		echo <<<HTML
<hr /><h1>Functions index</h1>
<ul>
	{$this->forall($this->functions,'<li>$value->link</li>')}
</ul>
HTML;
	}

	// }}}
	// {{{ index_interfaces()

	protected function index_interfaces()
	{
		if( ! $this->interfaces ) return null;
		echo <<<HTML
<hr /><h1>Interfaces index</h1>
<ul>
	{$this->forall($this->interfaces,'<li>$value->link</li>')}
</ul>
HTML;
	}

	// }}}
	// {{{ index_classes()

	protected function index_classes()
	{
		if( ! $this->classes ) return null;
		echo <<<HTML
<hr /><h1>Classes index</h1>
<ul>
	{$this->forall($this->classes,'<li>$value->link</li>')}
</ul>
HTML;
	}

	// }}}
	// {{{ element_filed()

	protected function element_filed( DstyleDoc_Element $element )
	{
		return <<<HTML
<dt>file</dt><dd>{$element->file->link}</dd>
<dt>line</dt><dd>{$element->line}</dd>
{$this->either($element->title,'<dt>title</dt><dd>'.$element->title.'</dd>')}
{$this->either($element->description,'<dt>description</dt><dd>'.$element->description.'</dd>')}
{$this->either($element->version,'<dt>version</dt><dd>'.$element->version.'</dd>')}
{$this->either($element->historys,'<dt>history</dt><dd>'.$this->forall($element->historys,'<li><b>{$value->version}: </b>{$value->description}</li>').'</dd>')}
{$this->either($element->packages,'<dt>package</dt><dd>'.$this->forall($element->packages,'<li>{$value}</li>').'</dd>')}
HTML;
	}

	// }}}
}
