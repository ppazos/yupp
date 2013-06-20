<?php

YuppLoader::load('twitter.model', 'TUser');
YuppLoader::load('twitter.model', 'Message');

$users = array();

$names = array(
'Abigail',
'Abraham',
'Adriana',
'Alexander',
'Andrew',
'Ariana',
'Barbara',
'Benjamin',
'Brandon',
'Brian',
'Burke',
'Cameron',
'Carmen',
'Carolina',
'Catherine',
'Celeste',
'Charles',
'Clara',
'Claudia',
'Coral',
'Dakota',
'Daniela',
'Dave',
'Dean',
'Denis',
'Derek',
'Duke',
'Elias',
'Elizabeth',
'Emerson',
'Ernest',
'Esmeralda',
);

$users[] = new TUser(array(
  'name' => 'Pablo',
  'email' => 'pablo.swp@gmail.com',
  'username' => 'pablo',
  'password' => 'pablo'
));

foreach ($names as $name)
{
   $users[] = new TUser(array(
      'name' => $name,
      'email' => String::firstToLower($name).'@gmail.com',
      'username' => String::firstToLower($name),
      'password' => String::firstToLower($name)
   ));
}

foreach ($users as $user)
{
   $user->save();
}

?>