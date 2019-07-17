
**Bootstrap**
```
include 'helpers.php';
include 'bootstrap.php';
```

**Registro de thumbs de Imagens**
```
(new Image('abc', 240))->addThumb();
```
	
/** Registro de Scripts **/
(new Scripts())
->addChunk(
	['a','b','c'],
	['1','2','3'],
	['x','y','z'])
->hook();

/** Registro de CPTs **/
$cpt = new CPT('destaques', new Label('Destaque','Destaques'));
$cpt->description = 'BLABLABLABLA';

(new CPTs($cpt))->hook();
