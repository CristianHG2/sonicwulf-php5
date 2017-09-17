<?php

$sitesGateAnon = function()
{
	$exp = explode('/', $_GET['siteid']);

	if ( count($exp) === 2 )
		$_GET['siteid'] = $exp[0];

	if ( !isset($_GET['siteid']) || !is_numeric($_GET['siteid']) )
		return array('redirect', 'https://diodiy.com/index.php');

	$s = Sites::select('*');
	
	if ( $s->where('id', $_GET['siteid'])->num(true) < 1 )
		return array('redirect', 'https://diodiy.com/index.php');

	$s = $s->run(true);
	$c = new ContProp('Site');

	foreach ( $s as $key => $val )
		$c->set($key, $val);

	$c->set('colorClass', $s['urlname']);

	ContProp::RegisterCP($c);

	global $page;

	if ( !isset($page['name']) )
		$page['name'] = $s['name'];
};