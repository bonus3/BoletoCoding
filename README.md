# Boleto Coding

Uma biblioteca PHP para extração de informações de um código de barras.
Atualmente, consegue-se extrair as seguintes informações:

  - Data de vencimento
  - Valor do documento

E suporta os seguintes formatos de boletos:
  - Concessionárias (Luz, telefone, gás, etc.) - Linha digitável de 48 digitos
  - Boleto bancário - Linha digitável de 47 digitos
  - Código de barras - 44 digitos

### Forma de usar

Inicializando:
```php
include_once "BoletoCoding\Boleto.php"; //Ou um autoload, se preferir

$linha_digitavel = "34191670060463564064460910580004739290000307014";
$boleto = new Boleto($linha_digitavel);
```

Ou com a linha digitável de um boleto de concessionária:
```php
$linha_digitavel = "828300000007943901072010710010111362699201709063";
$boleto = new Boleto($linha_digitavel);
```

A partir deste momento, estará disponível métodos de manipulação dos dados do código de barra.
```php
if ($boleto->is_valid()) {
    echo "Valor do documento: " . $boleto->get_subtotal(); // 3070.14
    echo "Data de vencimento: " . $boleto->get_due_date("d/m/Y");// 10/07/2008
}
```
