potassium
=========
A convenient PHP library for working with [Kasabi](http://kasabi.com/)

Overview
--------
This is a very early stage library for working with [Kasabi](http://kasabi.com/) APIs. When I say early stage, 
I mean I've only used it once (for my [English Heritage demo](http://iandavis.com/2011/english-heritage/)). 

The design philosophy of Potassium is to make things simple and get out of the way quickly. Potassium will
default to sensible values, guess common patterns, simplify overcomplex formats and try to return PHP native
types whenever possible. At the same time it allows you to tweak its behaviour and get access to the full
response from Kasabi so you can diagnose problems or program with the raw formats if you need to.

Examples
--------
Create a new instance

    $kasabi = new Potassium($api_key);

Perform a SPARQL select:

    $results = $kasabi->get('sparql-endpoint-foo', array('query'=>'select ?name ?age where {...'));

SPARQL select results are automatically simplified into a nested array: 

    [ {'name' => 'Rod', 'age' => 25}, {'name' => 'Jane', 'age' => 23}, {'name' => 'Freddy', 'age' => 30} ]

Full URI of Kasabi API is optional:

    $results = $kasabi->get('http://api.kasabi.com/api/sparql-endpoint-foo', array('query'=>'select...'));
  
Follow the happy path:
  
    $results = $kasabi->get('sparql-endpoint-foo', array('query'=>$query));
    if ($results) {
      // do something amazing...
    }
    else {
      $response = $kasabi->last_response();
      print "Failed with response: " . $response->responseCode;
      print "Body: " . $response->body;
      print "Headers: \n";
      print_r($response->headers);
    }


Licence
-------
This work is hereby released into the Public Domain. 

To view a copy of the public domain dedication, visit 
[http://creativecommons.org/licenses/publicdomain](http://creativecommons.org/licenses/publicdomain) or send a letter to 
Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
