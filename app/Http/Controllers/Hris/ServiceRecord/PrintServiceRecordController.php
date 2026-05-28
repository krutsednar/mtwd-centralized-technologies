<?php

namespace App\Http\Controllers\Hris\ServiceRecord;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use PDF;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the printable Service Record PDF for a single profile.
 *
 * Uses wkhtmltopdf via barryvdh/laravel-snappy (\PDF facade). The repeating
 * page header and footer are rendered to temp HTML files first so wkhtmltopdf
 * can stamp them on every page via its --header-html / --footer-html options.
 *
 * Extracted from the route closure formerly at routes/web.php (audit R-1).
 * Route name `service-record.print` preserved — referenced from
 * ServiceRecordResource.
 */
class PrintServiceRecordController extends Controller
{
    public function __invoke(Profile $profile): Response
    {
        $profile->load('serviceRecords');
        $serviceRecords = $profile->serviceRecords()->orderBy('from')->get();

        // Render the repeating header and footer as standalone HTML temp files.
        // wkhtmltopdf stamps them on every page via --header-html / --footer-html.
        $headerHtml = view('pdf.service-record-header')->render();
        $footerHtml = view('pdf.service-record-footer')->render();

        $tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $tmpHeader = $tmpDir.'sr_hdr_'.uniqid().'.html';
        $tmpFooter = $tmpDir.'sr_ftr_'.uniqid().'.html';

        file_put_contents($tmpHeader, $headerHtml);
        file_put_contents($tmpFooter, $footerHtml);

        try {
            return PDF::loadView('pdf.service-record-body', compact('profile', 'serviceRecords'))
                // 8.5 × 13 inch (Folio)
                ->setOption('page-width', '215.9mm')
                ->setOption('page-height', '330.2mm')
                // Side gutters
                ->setOption('margin-left', '12.7mm')
                ->setOption('margin-right', '12.7mm')
                // Top margin must be ≥ rendered header height + header-spacing
                ->setOption('margin-top', '50mm')
                ->setOption('header-html', $tmpHeader)
                ->setOption('header-spacing', '3')
                // Bottom margin must be ≥ rendered footer height + footer-spacing
                ->setOption('margin-bottom', '18mm')
                ->setOption('footer-html', $tmpFooter)
                ->setOption('footer-spacing', '2')
                // Allow reading local image files (file:/// paths in header/footer views)
                ->setOption('enable-local-file-access', true)
                ->inline('service-record-'.$profile->employee_number.'.pdf');
        } finally {
            @unlink($tmpHeader);
            @unlink($tmpFooter);
        }
    }
}
