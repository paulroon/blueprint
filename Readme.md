<h1 align="center"><!-- NAME_START -->Blueprint<!-- NAME_END --></h1>

<p align="center">
    <strong>A PHP Library for Defining, validating, reading and transforming 3rd Party data.</strong>
</p>

<!-- OK_START -->
## Problem

[disclaimer] - This is JSON only for now. Everything else is coming soon!

Ok, so it's a common thing. Your app talks to an API somewhere.

You get some JSON back from a 3rd party request, it seems to have everything you need in it (somewhere) but now you have to work out what to do with it. 

You have 2 options: 
 - json_decode and start passing the result around. - and with that you've spread that madness to every corner of your app.
 - start building custom objects and hydrating data - then about a month later someone asks why there's a PR with 200 new files in it, and you nearly died of boredom.

oh no, no, no, NO!!

<!-- BLUEPRINT_START -->
## Blueprint


#### Tldr;
 - Defines the shape of your data
 - Validates input
 - Applies Transformations
 - Produces Access Objects (Models) the way you want them
 - Renders back to your custom / desired json

Blueprint allows us to **DEFINE** the data we're expecting, like a table in a DB. We tell it the **SCHEMA** (the shape and types) of the JSON, 
We tell it how to **validate** it, our rules not theirs, we **filter** out the stuff we won't want, and how to change the bits we won't like. 

Then we give it the data.

Validation is implicit, with detailed errors on what went wrong.

It gives us back smart Objects, Safe data, Structured and Formatted the way our apps need it.

Lets have a look...
<!-- INSTALL_START -->
## Install

``` bash
composer require happycode/blueprint
```

<!-- USAGE_START -->
## Usage

let's say we get this from a 3rd party API - it's in a variable called `$json`
``` json
  {
    "id": 1,
    "firstName": "Roger",
    "lastName": "Rabbit",
  }
```

Let's try to define it. 
``` php
    use HappyCode\Blueprint\Model;

    $userSchema = Model::Define('User', [
        "id" => 'integer',
        "firstName" => 'string',
        "lastName" => 'string',
    ])
```
Now we can add some data
``` php
    $user = $userSchema->adapt($json);
```
by the way - that was where the validation happened!
and so now...
``` php
    echo $user->getFirstName();
```
will give you what you might imagine.

nice! - lets go deeper. 

### Components
 
 - [Types](#types)
 - [Meta-data](#meta-data)
 - [Enum](#enum)
 - [DateTime](#datetime)
 - [ArrayOf](#arrayof)
 - [Non-Primitives](#non-primitives-custom-objects)
 - [Collections](#collections)
 - [Virtual Fields](#virtual-fields-transformations)
 - [Shorthand](#shorthand)

### Types

when we specify a field...
```php
    $userSchema = Model::Define('User', [
        "fieldName" => 'string',
    ])
```
its actually shorthand for
```php
    $userSchema = Model::Define('User', [
        "fieldName" => Type::String(),
    ])
```
the `Type` Class gives us access to make more complex configuration. 

The available Primitive types are
```Type::String()```
```Type::Int()```
```Type::Float()```
```Type::Boolean()```

### Meta-Data
All Types have some associated meta-data, specifically the following booleans
 - `isNullable` - when true the field is allowed a `null` value
 - `isRequired` - when true a validation error will occur if the field does not exist
 - `isHidden` - when true a required field will not be rendered in transformed data (more on this later)
 they can be set as follows
```php
    $userSchema = Model::Define('User', [
        "fieldName" => Type::String(isNullable: true, isRequired: false, isHidden: false),
    ])
```
The default values are  ( x denotes a property that cannot be set )


|            | isNullable | isRequired | isHidden |
|------------|:----------:|:----------:|:--------:|
| String     |   false    |    true    |  false   |
| Boolean    | x (false)  |    true    |  false   |
| Float      |   false    |    true    |  false   |
| Int        |   false    |    true    |  false   |
| DateTime   |   false    |    true    |  false   |
| Enum       | x (false)  |    true    |  false   |
| ArrayOf    |   false    |    true    |  false   |
| Collection |   false    |    true    |  false   |


### Enum

Enumerated values - will only accept values that match the specified set
example 
```php
    $userSchema = Model::Define('User', [
        "status" => Type::Enum(values: ["PENDING", "ACTIVE", "DISABLED"]),
    ])
```
### DateTime

Dates and times are a common use case for inline transformations and so have we're able to create PHP date format specifications for reading and rendering.
example
```php
    $userSchema = Model::Define('User', [
        "status" => Type::DateTime(inputFormat: 'd-m-Y', outputFormat: 'm/d/Y'),
    ])
```
the defaults are 
 - inputFormat - 'd/m/y H:i:s'
 - output format - whatever the input format is

### ArrayOf
 Suppose we're looking at this kind of json
``` json
  {
    ...
    "lotteryNumbers": [1,2,3,4,5,6]
    ...
  }
```
Because it can be described as 'an array of primitive types' we can model it like this... 

```php
    $userSchema = Model::Define('User', [
        ...
        "lotteryNumbers" => Type::ArrayOf(Type::Int(), isNullable: true),
        ...
    ])
    // or with shorthand
    $userSchema = Model::Define('User', [
        ...
        "lotteryNumbers" => 'int[]'
        ...
    ])
```
### Non-Primitives (Custom Objects)
Yep nested structures can be typed too...
Here's an example
``` json
  {
    ...
    "geoLocation": {
      "lat": '84.9999572",
      "long": "-135.000413,21"
    }
    ...
  }
```

We can create a custom (sub) Model on the fly
```php
    $mapPinSchema = Model::Define('MapPin', [
        ...
        "geoLocation" => Type::Model(
            Model::Define('GeoLocation', [
                  "lat" => Type::String(),
                  "long" => Type::String()
            ]),
            isNullable: true
        ),
        ...
    ])
```

although you could always make it more reusable should the schema need to repeat the object

``` json
  {
    ...
    "pickupLocation": {
      "lat": '84.9999572",
      "long": "-135.000413,21"
    },
    "dropLocation": {
      "lat": '49.4296032",
      "long": "0.737196,7"
    }
    ...
  }
```
like...
```php
    $geoLocationSchema = Model::Define('GeoLocation', [
          "lat" => Type::String(),
          "long" => Type::String()
    ]);
    $deliverySchema = Model::Define('Delivery', [
        ...
        "pickupLocation" => Type::Model($geoLocationSchema, isNullable: true),
        "dropLocation" => Type::Model($geoLocationSchema),
        ...
    ])
```

### Collections

ok - lets combine Custom Objects and Arrays - in Blueprint a set of Custom Objects (Model Schemas) is called a Collection

Using the `$geoLocationSchema` from the custom object example (above)

```php
    $geoLocationSchema = Model::Define('GeoLocation', [
          "lat" => Type::String(),
          "long" => Type::String()
    ]);
    
    $deliverySchema = Model::Define('Delivery', [
        ...
        "journeyTracking" => Type::Collection($geoLocationSchema, isNullable: true),
        ...
    ])
```

allows for json like 

``` json
  {
    ...
    "journeyTracking": [
       { "lat": '84.9999572", "long": "-135.000413,21" },
       { "lat": '49.4296032", "long": "0.737196,7" },
    ]
    ...
  }
```

### RootCollections

Sometimes json will have a root level array instead of an Object - this is valid, annoying and quite common.
```json
[
 {
  "name": "Roger"
 },
 {
  "name": "Jessica"
 }
]
```
easily done...
```php
    $rabbitSchema = Model::Define('Rabbit', [
          "name" => Type::String()
    ]);
    
    $loadsOfRabbitsSchema = Model::CollectionOf($rabbitSchema)
```

### Virtual Fields (Transformations)

Suppose you want a field that isn't there, and you can construct it from the data you already have.
for example:
```json
{
 "first": "Roger",
 "last": "Rabbit"
}
```
... don't you just with you had a `fullName` field in there. well...
```php
    $rabbitSchema = Model::Define('Rabbit', [
          "first" => Type::String(isHidden: true),
          "last" => Type::String(isHidden: true),
          "fullName" => Type::Virtual(function($rabbit) {
                return $rabbit['first'] . ' ' . $rabbit['last'];
          }),
    ]);
```
The function passed to the `Type::Virtual()` method will have an assoc array with all the decoded properties (including hidden) and values from the input json. 

The only requirement is that any fields being used are present in the schema (obviously).

By the way, this is why we might want to hide fields using `isHidden` - when we get to `rendering` the hydrated models, we may not want them visible.

### Shorthand

As long as you aren't messing with the default values, Primitive types all have shorthand notation, in case you find that more readable.
```php
    $schema = Model::Define('Thing', [
          "name" => Type::String(),
          "weight" => Type::Int(),
          "lotteryNumbers" => Type::ArrayOf(Type::Int())
    ]);
// is equivalent to
    $schema = Model::Thing([
          "name" => 'string',           // or 'text'
          "rich" => 'bool',             // or 'boolean'
          "weight" => 'float',          // 'double' or 'decimal' also work
          "lotteryNumbers" => 'int[]'   // works for all primitive types
    ]);
```


### Adapting (Hydrating)

Hoo wee Tiger!!! - You have a schema! Nice work!

Lets 
```php
    $model = $schema->adapt($jsonString);
```
1. The first thing that happens here is `Validation` - Blueprint knows what it needs from the json so it makes sure it's there. 
2. The second thing is `Filtering` - If there's data in the Json, but your schema doesn't define it anywhere, it's disregarded.
3. and the last thing that happens is it returns a model representing your data the way you need it,

watch this...

```php
    $user = (Model::Define('User', [ 'name' => 'string' ])->adapt('{ "name": "Roger" }'));

    echo $user->getName(); // Roger
```

### Rendering
now this

```php
    $user = (Model::Define('User', ['name' => 'string']))->adapt('{ "name": "Roger" }');

    echo $user->json(); // Roger
```

Well that's pointless right?? - or is it?

```php
    $spy = (Model::Define('Spy', [
        "first" => Type::String(isHidden: true),
        "last" => Type::String(isHidden: true),
        "fullName" => Type::Virtual(function($who) {
                return sprintf("%s, %s %s!", $who['last'], $who['first'], $who['last']);
        }),
    ])->adapt('{ "first": "James", "last": "Bond" }'));

    echo $spy->json(); // A string = {"fullName":"Bond, James Bond!"}
```

### IDE's and Type Help
$model->json()


<!-- USAGE_END -->


<!-- COPYRIGHT_START -->
## Copyright and License

The happycode/blueprint library is copyright © [Paul Rooney](mailto://blueprint@happycoder.co.uk)
and licensed for use under the terms of the
MIT License (MIT). Please see [LICENSE](https://mit-license.org/) for more information.
<!-- COPYRIGHT_END -->