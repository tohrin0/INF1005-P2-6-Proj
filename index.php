<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
</head>
<body class="container">
    <?php 
    include "inc/header.inc.php";
    include "inc/nav.inc.php";
    ?>
    <main>
        <section id="dogs" class="mb-4">
            <h2>All About Dogs!</h2>
            <div class="row">
                <article class="col-sm">
                    <figure>
                        <h3>Poodle</h3>
                        <img src="images/poodle_small.jpg" alt="Poodle" class="img-fluid img-thumbnail popup-img" data-large="images/poodle_large.jpg"/>
                    </figure>
                    <p>Poodles are a group of formal dog breeds, the Standard Poodle, Miniature Poodle and Toy Poodle.</p>
                </article>
                <article class="col-sm">
                    <figure>
                        <h3>Chihuahua</h3>
                        <img src="images/chihuahua_small.jpg" alt="Chihuahua" class="img-fluid img-thumbnail popup-img" data-large="images/chihuahua_large.jpg"/>
                    </figure>
                    <p>The Chihuahua is the smallest breed of dog, and is named after the Mexican state of Chihuahua.</p>
                </article>
            </div>
        </section>
        
        <section id="cats" class="mb-4">
            <h2>All About Cats!</h2>
            <div class="row">
                <article class="col-sm">
                    <figure>
                        <h3>Tabby</h3>
                        <img src="images/tabby_small.jpg" alt="Tabby" class="img-fluid img-thumbnail popup-img" data-large="images/tabby_large.jpg"/>
                    </figure>
                    <p>A tabby is any domestic cat (Felis catus) with a distinctive 'M' shaped marking on its forehead, stripes by its eyes and across its cheeks, along its back, and around its legs and tail.</p>
                </article>
                <article class="col-sm">
                    <figure>
                        <h3>Calico</h3>
                        <img src="images/calico_small.jpg" alt="Calico" class="img-fluid img-thumbnail popup-img" data-large="images/calico_large.jpg"/>
                    </figure>
                    <p>The calico cat is most often thought of as being 25% to 75% white with large orange and black patches. They are the official cat of Maryland.</p>
                </article>
            </div>
        </section>
    </main>
    <?php include "inc/footer.inc.php"; ?>
</body>
</html>