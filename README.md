# sprinkle-crud5

CRUD Sprinkle for Userfrosting 5.0

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
