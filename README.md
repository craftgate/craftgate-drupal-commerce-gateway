# Craftgate Payment Gateway
Craftgate Drupal Commerce 2.x modülünü kullanarak işletmenize ait tüm bankaların Sanal POSlarını tek merkezden yönetebilir, gelişmiş ödeme formu entegrasyonu ile kolayca ve güvenle ödeme alabilirsiniz.

### Craftgate nedir?
Craftgate, bulut tabanlı ödeme geçidi ile işletmenize ait tüm bankaların Sanal POSlarını tek merkezden kolayca entegre edebilir, kart saklama, tek tıkla ödeme, pazaryeri çözümü, kapalı devre cüzdan, ödeme formu, ortak ödeme sayfası, gelişmiş üye işyeri kontrol paneli, gelişmiş API gibi birçok katma değerli servisten hızlı bir şekilde yararlanabilirsiniz. Böylece, maliyetlerinizi düşürürken, siz asıl işinizi büyütmeye odaklanabilirsiniz.

### Craftgate ile çalışmaya nasıl başlayabilirim?
1. [craftgate.io](https://craftgate.io) sayfamızdan Kayıt Ol butonuna tıklayınız.
1. Sizden istenilen bilgileri doldurup, belgeleri yükleyip, online olarak başvurunuz.
1. Gelen dijital sözleşmeyi onaylayın ve Craftgate’i hemen kullanmaya başlayınız.

### Ürün özellikleri
* Tüm bankalarla hazır Sanal POS entegrasyonu
* Pazaryeri, alt üye işyeri ve para dağıtma modeli desteği
* PCI-DSS-1 uyumlu kart saklama, tek tıkla ödeme ve abonelik çözümü
* Kapalı devre cüzdan çözümü
* Ödeme formu, ortak ödeme sayfası
* Akıllı ve Dinamik Ödeme Yönlendirme Kuralları
* Ödeme Tekrar Deneme
* Gelişmiş Üye İşyeri kontrol paneli
* Birçok programlama dilini kapsayan, kolay entegre edilebilir API
* 2007 yılından bu yana bir çok tecrübe ve birikimle geliştirilmiş, ölçeklenebilir, 6. nesil platform

### Gereksinimler
Craftgate kullanarak ödeme geçirebilmek için üyeliğinizin olması gerekmektedir.

### Kurulum
#### Drupal'a manuel modül yüklenmesi
* Repodan klasör olarak indirilen modül Drupal içerisinde yer alan "web/modules/custom" klasörüne kopyalanır. 
* Kurulum composer ile olmadığı için craftgate/craftgate kütüphanesi gerekeceğinden Drupal ana dizininde "composer require craftgate/craftgate" ile yüklenmesi gerekmektedir.

#### Drupal'a drupal.org üzerinden modül yüklenmesi
* Modül drupal.org'a yüklendikten sonra composer ile modül yüklenebilmektedir. 
* drupal.org üzerinde modülün adı "commerce_craftgate" olduğu için "composer require drupal/commerce_craftgate" ile modülün kuruluma eklenmesi sağlanabilir. (örnek olarak modül drupal.org'a yüklenmeden "composer require drupal/commerce_paypal" komutu ile commerce_paypal modülü yüklenip test edilebilir)

### Geliştiriciler
Drupal Commerce 2.x eklentisi [Bidolubaskı](https://github.com/bidolubaski) tarafından geliştirilmiştir ve artık Craftgate tarafından bakımı yapılmaktadır.
