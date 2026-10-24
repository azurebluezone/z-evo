# Demo product images — provenance and licences

Every published product needs at least three photos on its detail page. The
seeded catalogue had one per product (and ten products pointed at
`uploads/category/default.png`, the "IMAGE NOT AVAILABLE / Developer Zone"
graphic), so the gallery strip never appeared and the home rail looked broken.

`php artisan products:gallery` fixes that. It never invents a photo of a
different product: it either fills a placeholder from the demo sources below,
or it derives two extra shots from the product's own main photo.

## Generated shots

| File | How it is made |
| --- | --- |
| `{id}-01.jpg` | 900x900 cover crop of the product's main photo (or of the demo source when the main was a placeholder) |
| `{id}-02.jpg` | 800x1000 (4:5) centre crop of `{id}-01.jpg` |
| `{id}-03.jpg` | 800x800 macro (55% centre crop) of `{id}-01.jpg` |

Output lives in `public/uploads/product/gallery/`, never in
`public/uploads/product/` itself, so generated files stay distinguishable from
an admin's own uploads. `public/uploads/*` is git-ignored by this repository's
policy; the files are recreated by the command, and the rows in
`productimages` are the source of truth for which file a product uses.

`--force` re-derives the files that belong to an existing row (for example
after a crop rule changes). It never adds a row to a product that already has
enough photos, and it never overwrites a main image the command did not
generate itself.

## Third-party demo photos

These are placeholders for products whose catalogue row had no photo at all.
They are **not** photographs of the actual goods on sale.

| Local file | Product | Source | Licence | Author / page |
| --- | --- | --- | --- | --- |
| `product/demo/winter-hoodie-navy.jpg` | P2 Adora Winter Hoodie | Flickr photo `49067425856`, via Openverse | CC0 1.0 (as listed by the uploader) | <https://www.flickr.com/photo.gne?id=49067425856> |
| `product/demo/clock-regulator.webp` | P3 Classic Wall Clock - WC-10 | rawpixel, via Openverse | CC0 1.0 | <https://www.rawpixel.com/image/7488157/image-face-wooden-building> |
| `product/demo/gift-box-white.jpg` | P4 Combo Set - Gift Pack | Flickr, "GIFT BOX POWER" | CC0 1.0 | skeyndor.oficial, <https://www.flickr.com/photos/57794015@N05/51535012929> |
| `product/demo/mens-kurta-blue.jpg` | P5 Men's Panjabi Premium | Wikimedia Commons, "Blue khadi kurta" | **CC BY-SA 3.0** | Purva4u, <https://commons.wikimedia.org/wiki/File:Blue_khadi_kurta.jpg> |
| `product/demo/womens-abaya-black.jpg` | P6 Woman's Abaya Classic | Wikimedia Commons, "Abaya (overkleed)" | CC0 1.0 | <https://commons.wikimedia.org/wiki/File:Abaya_(overkleed),_asset_ymilOPVMiWoPZZKr6eJ9Av4k.jpg> |
| `product/demo/clock-pendulum-dog.jpg` | P8 Wall Clock - Minimal 02 | Flickr, "Giftgarden Pet Gift Pendulum Wall Clock with Puppy Dog Shaped" | Public Domain Mark 1.0 | favorli, <https://www.flickr.com/photos/147778363@N07/33098577346> |

Products P1, P7, P9 and P10 use the shop's own uploads under
`public/uploads/campaign/` and `public/uploads/landing-pages/`.

### Before a real launch

- The hoodie (Carhartt) and gift box (Skeyndor) photos are third-party product
  shots that an uploader tagged CC0; the underlying brand photography is not
  the uploader's to license. Replace them with the store's own photographs.
- The kurta is CC BY-SA 3.0: the generated crops are adaptations, so they carry
  the same licence and need the attribution above wherever they are published.
  Replacing it with an own photo removes the obligation entirely.
- P1/P7 carry the mockup's printed "Product Code: CWC-201 / 36 x 16 inch"
  caption. That is the product's own specification, not another brand's.

## Reproducing

```bash
php artisan products:gallery --dry-run   # report only
php artisan products:gallery             # fill placeholders, add missing shots
php artisan products:gallery --force     # re-derive existing generated files
php artisan products:gallery --min=3     # minimum photos per published product
```

The command is idempotent: a second plain run reports
`files_written=0 rows_inserted=0`. It only touches products with
`status = 1`.
