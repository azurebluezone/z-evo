<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

/**
 * InteractsWithDataTable
 * ----------------------
 * The parts every admin grid endpoint needs, so six list screens do not each
 * carry their own copy of them.
 *
 * WHAT THE GRID EXPECTS
 * ---------------------
 * One array entry per row, one element per column, each already-rendered HTML
 * from the page's own <page>/_row.blade.php. Client-side paging is not an
 * option: the orders list can hold tens of thousands of rows, and shipping them
 * all to the browser to sort in javascript is how a list stops opening.
 *
 * WHY CELLS ARE SPLIT HERE
 * -----------------------
 * DataTables writes each value into a cell it creates itself. A value that
 * still carries its own <td> becomes <td><td>...</td></td>, and the browser
 * repairs that nesting by hoisting the content out - printing every cell once
 * per column. So the split is not cosmetic; it is what makes the grid render
 * correctly at all.
 *
 * The cell count is checked against the column count on every request. A row
 * partial that gains or loses a cell therefore fails loudly here instead of
 * quietly showing every column one place to the left on every row.
 */
trait InteractsWithDataTable
{
    /** Maximum rows a single page may ask for. */
    protected int $dtMaxPageLength = 200;

    /**
     * Split a rendered <tr> into cell contents (the <td> itself is removed).
     *
     * @throws \RuntimeException when the cell count does not match the grid
     */
    protected function dtCells(string $rowHtml, int $expectedColumns, string $partialName): array
    {
        $inner = preg_replace('#^<tr[^>]*>|</tr>$#i', '', trim($rowHtml)) ?? trim($rowHtml);
        $inner = preg_replace('#^</?t[bo][dy]>#i', '', $inner) ?? $inner;

        $cells = preg_split('#</td>#i', $inner) ?: [];

        // The tail after the final </td> is empty by construction.
        if (end($cells) === false || trim((string) end($cells)) === '') {
            array_pop($cells);
        }

        $cells = array_values(array_filter(
            array_map('trim', $cells),
            static fn (string $cell): bool => $cell !== ''
        ));

        if (count($cells) !== $expectedColumns) {
            throw new \RuntimeException(sprintf(
                '%s rendered %d cells but its grid declares %d columns. '
                . 'Fix the row partial or the grid\'s columns.',
                $partialName,
                count($cells),
                $expectedColumns
            ));
        }

        return array_map(
            static fn (string $cell): string => trim(
                (string) preg_replace('#^<td[^>]*>|</td>$#i', '', trim($cell))
            ),
            $cells
        );
    }

    /**
     * Read the paging window from the request, clamped.
     *
     * @return array{0:int,1:int} start, length
     */
    protected function dtPaging(Request $request): array
    {
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 20);

        return [$start, $length > 0 ? min($length, $this->dtMaxPageLength) : 20];
    }

    /**
     * The text the grid's search box holds.
     *
     * DataTables sends it as `search[value]=...` in the query string. Laravel
     * does NOT read that back with input('search[value]'): input() resolves its
     * key with data_get(), which speaks dot notation, not PHP's bracket syntax.
     * The lookup therefore returns null, the grid's search box silently does
     * nothing, and the list looks broken in a way that has nothing to do with
     * the query. Hence 'search.value' - the same key, written the way it is
     * actually read.
     *
     * The old page-level ?keyword= is still accepted so a bookmarked URL keeps
     * working.
     */
    protected function dtSearch(Request $request): string
    {
        $value = $request->input('search.value');

        if ($value === null || $value === '') {
            $value = $request->input('keyword', '');
        }

        return trim((string) $value);
    }

    /**
     * The column index the grid wants sorted, and its direction.
     *
     * Dot notation for the same reason as dtSearch(): DataTables sends
     * `order[0][column]=2&order[0][dir]=asc`, which arrives as
     * ['order' => [['column' => 2, 'dir' => 'asc']]].
     *
     * The caller supplies the whitelist, because only it knows what its table
     * prints. Anything not in the whitelist falls back rather than reaching the
     * query builder.
     *
     * @param  array<int,string>  $sortable  column index => database column
     * @return array{0:string,1:string}
     */
    protected function dtOrdering(Request $request, array $sortable, string $default): array
    {
        $column = (int) $request->input('order.0.column', -1);
        $dir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        return [$sortable[$column] ?? $default, $dir];
    }

    /**
     * The JSON envelope DataTables expects.
     *
     * recordsTotal is how many rows the table has; recordsFiltered is how many
     * the current search leaves. The pager counts the second one, so returning
     * the total for both makes "Showing 1 to 20 of 276" appear even when a
     * search matched three rows - which is the visible symptom of a search that
     * is not being applied.
     *
     * @param  array<int,array<int,string>>  $rows  one entry per row, one item per column
     */
    protected function dtResponse(int $draw, int $total, int $filtered, array $rows): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
    }
}