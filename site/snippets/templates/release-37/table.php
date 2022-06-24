<style>
.v37-table-grid {
  display: grid;
  grid-gap: var(--spacing-6);
  grid-template-columns: 1fr;
  grid-template-areas: "figure"
                       "box1"
                       "box2";
}

@media screen and (min-width: 45rem) {
  .v37-table-grid {
    grid-template-columns: 1fr 1fr;
    grid-template-areas: "figure figure"
                        "box1 box2";
  }
}
</style>

<section id="table-layout" class="mb-42">

  <?php snippet('hgroup', [
    'title'    => 'New table layout',
    'subtitle' => 'Who said spreadsheets cannot be cool?',
    'mb'       => 12
  ]) ?>

  <div class="v37-table-grid">

    <figure class="release-box bg-light" style="--aspect-ratio: 2633/805; grid-area: figure">
      <img src="<?= ($image = $page->image('table.png'))->url() ?>" loading="lazy">
    </figure>

    <div class="release-text-box" style="grid-area: box1">
      <h3>At a glance</h3>
      <div class="prose">
        With the brand new table layout, you get a great overview of the content of your pages. Customize the columns you want to show to present exactly the data you need.
      </div>
    </div>
    <div class="release-code-box" style="grid-area: box2">
      <?= $page->table()->kt() ?>
    </div>
  </div>
</section>
