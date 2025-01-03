# sprinkle-crud5

CRUD Sprinkle for Userfrosting 5.0

CRUD Functionality for all database tables

Feature List

1. List view : Common base class that uses schema/crud5.<model>.yaml file to create a list page for the table contents accessible at
   /crud5/<model-name>. This utilizes pre defined Handelbars templates in a flat table view.
   - TO-DO:
     - one option specify handelbars template in the YAML so the user can set this dynamically (Can we load handlebar templates from a file ?)
       https://handlebarsjs.com/api-reference/compilation.html#handlebars-compile-template-options
     - create twig template files for the column format with handlebars template in it, load that dynamically in the
2. /api/crud5/<model-name> for all the CRUD Operations
3. /modals/crud5/<model-name>/<opertaion> : the CRUD5Injector is giving an error
   TODO: Need to learn how injector works here, goal is to dynamically set the the model based on the crud_slug parameter from the Route

```
require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$data = [
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'age' => 25,
];

$yaml = Yaml::dump($data);

file_put_contents('data.yaml', $yaml);

echo "Array converted to YAML and saved as 'data.yaml'.";

```

```
$data = [
    'name' => 'Michael Johnson',
    'email' => 'michael@example.com',
    'age' => 35,
];

$yaml = yaml_emit($data);

file_put_contents('data.yaml', $yaml);

echo "Array converted to YAML and saved as 'data.yaml'.";
```
