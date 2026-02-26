<!-- JAVASCRIPT -->

<!-- 1️⃣ jQuery (FIRST – required by everything else) -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>

<!-- 2️⃣ Bootstrap -->
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<!-- 3️⃣ Simplebar (used by sidebar scroll) -->
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js') }}"></script>

<!-- 4️⃣ MetisMenu (sidebar menu) -->
<script src="{{ URL::asset('build/libs/metismenu/metisMenu.min.js') }}"></script>

<!-- 5️⃣ Waves (ripple effects – optional but required by app.js) -->
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js') }}"></script>

<!-- 6️⃣ Icons (safe to keep here) -->
<script src="https://unicons.iconscout.com/release/v2.0.1/script/monochrome/bundle.js"></script>

<!-- 7️⃣ APP JS (MUST BE LAST) -->
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>
    (function () {
        function normalizedText(cell) {
            return (cell.innerText || '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function isSingleLine(cell) {
            const text = normalizedText(cell);
            return text !== '' && !text.includes('\n');
        }

        function getVisibleRows(table) {
            if (!table.tBodies || !table.tBodies.length) {
                return [];
            }

            return Array.from(table.tBodies[0].rows).filter(function (row) {
                return row.offsetParent !== null;
            });
        }

        function applyAutoCenterToTable(table) {
            const rows = getVisibleRows(table);
            if (!rows.length) {
                return;
            }

            const colCount = Math.max.apply(null, rows.map(function (row) {
                return row.cells.length;
            }));

            for (let col = 0; col < colCount; col++) {
                const columnCells = rows
                    .map(function (row) { return row.cells[col]; })
                    .filter(Boolean);

                if (!columnCells.length) {
                    continue;
                }

                const texts = columnCells.map(normalizedText);
                const allSingleLine = columnCells.every(isSingleLine);
                const uniqueLengths = new Set(texts.map(function (text) { return text.length; }));
                const shouldCenter = allSingleLine && uniqueLengths.size === 1 && !uniqueLengths.has(0);

                columnCells.forEach(function (cell) {
                    cell.classList.toggle('auto-center-column', shouldCenter);
                });

                if (table.tHead && table.tHead.rows.length) {
                    const headCell = table.tHead.rows[table.tHead.rows.length - 1].cells[col];
                    if (headCell) {
                        headCell.classList.toggle('auto-center-column', shouldCenter);
                    }
                }
            }

            rows.forEach(function (row) {
                Array.from(row.cells).forEach(function (cell) {
                    const hasActions = cell.querySelector(
                        '.btn-group, .btn-toolbar, a.btn, button.btn, form button.btn'
                    );
                    cell.classList.toggle('table-action-cell', Boolean(hasActions));
                });
            });
        }

        function applyAutoCenterToAllTables() {
            document.querySelectorAll('table').forEach(applyAutoCenterToTable);
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyAutoCenterToAllTables();
            window.setTimeout(applyAutoCenterToAllTables, 250);
        });

        if (window.jQuery) {
            jQuery(document).on('draw.dt', function () {
                applyAutoCenterToAllTables();
            });
        }
    })();
</script>

<!-- 8️⃣ Page-specific scripts -->
@yield('scripts')
