<?php

namespace Tests\Feature;

use App\Models\NotificationTemplate;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Tests\TestCase;

/**
 * Template Manager UI gate.
 *
 * The three bulk-send screens (newsletter, contacts, service requests) use the
 * same NotificationTemplate rows as the Template Manager, so each one must link
 * there; the manager link belongs inside the User Submissions submenu instead of
 * standing alone; and the create/edit pages must not emit their stylesheet
 * before <!doctype html>, carry a second page heading, or keep the old helper
 * lines under the fields.
 *
 * This is the assertion body behind tools/verify_notification_template_ui.php.
 * It prints NOTIFICATION_TEMPLATE_UI_OK only after every check passes.
 */
class NotificationTemplateUiCheckTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user to authenticate as.');

        return $admin;
    }

    private function xpath(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    public function test_template_manager_ui(): void
    {
        $admin = $this->admin();
        $managerUrl = route('admin.notification-templates.index');

        // 1. Every bulk-send screen offers the same Template Manager.
        foreach ([
            'admin.user-submissions.newsletter',
            'admin.user-submissions.contacts',
            'admin.user-submissions.service-requests.index',
        ] as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName));
            $response->assertOk();
            $response->assertSee($managerUrl, false);
        }

        // 2. The sidebar has exactly one manager link, inside User Submissions.
        $create = $this->actingAs($admin)->get(route('admin.notification-templates.create'));
        $create->assertOk();
        $createHtml = $create->getContent();
        $createXpath = $this->xpath($createHtml);

        $aside = $createXpath->query("//aside[@id='layout-menu']")->item(0);
        $this->assertNotNull($aside, 'the admin sidebar is missing');
        $sidebarLinks = $createXpath->query(".//a[contains(@href, '/admin/notification-templates')]", $aside);
        $this->assertSame(1, $sidebarLinks->length, 'the sidebar must hold exactly one Template Manager link');

        $userSubmissions = $createXpath->query(".//a[contains(normalize-space(.), 'User Submissions')]", $aside)->item(0);
        $this->assertNotNull($userSubmissions, 'the User Submissions menu entry is missing');
        $menuItem = $userSubmissions;
        while ($menuItem && !($menuItem instanceof DOMElement && strtolower($menuItem->nodeName) === 'li')) {
            $menuItem = $menuItem->parentNode;
        }
        $this->assertNotNull($menuItem, 'User Submissions is not inside a menu item');
        $inSubmenu = $createXpath->query(".//a[contains(@href, '/admin/notification-templates')]", $menuItem);
        $this->assertSame(1, $inSubmenu->length, 'Template Manager must sit inside the User Submissions submenu');

        // 3. Create: doctype first, stylesheet in head.
        $this->assertStringStartsWith('<!doctype html>', strtolower(ltrim($createHtml)), 'markup is emitted before the doctype');
        $this->assertSame(
            1,
            $createXpath->query("//head//link[contains(@href, 'admin-page-notification-templates.css')]")->length,
            'the template stylesheet must be inside head'
        );

        // 4. Edit: same structure, plus the page must still render.
        $template = NotificationTemplate::query()->first();
        $this->assertNotNull($template, 'seed at least one notification template');

        $edit = $this->actingAs($admin)->get(route('admin.notification-templates.edit', $template->id));
        $edit->assertOk();
        $editHtml = $edit->getContent();
        $editXpath = $this->xpath($editHtml);

        $this->assertStringStartsWith('<!doctype html>', strtolower(ltrim($editHtml)), 'markup is emitted before the doctype');
        $this->assertSame(
            1,
            $editXpath->query("//head//link[contains(@href, 'admin-page-notification-templates.css')]")->length,
            'the template stylesheet must be inside head'
        );

        // 5. No second page heading above the card.
        $this->assertSame(0, $createXpath->query("//h4[normalize-space(.)='Create Template']")->length, 'duplicate Create Template heading');
        $this->assertSame(0, $editXpath->query("//h4[normalize-space(.)='Edit Template']")->length, 'duplicate Edit Template heading');

        // 6. No helper lines under the fields, in either form.
        foreach ([
            ['adminForm', $createXpath],
            ['edit-notification-templateForm', $editXpath],
        ] as [$formId, $xpath]) {
            $form = $xpath->query("//form[@id='{$formId}']")->item(0);
            $this->assertNotNull($form, "form {$formId} is missing");
            $helpers = $xpath->query(
                ".//*[contains(concat(' ', normalize-space(@class), ' '), ' form-text ')]",
                $form
            );
            $this->assertSame(0, $helpers->length, "form {$formId} still has form-text helper lines");
        }

        fwrite(STDOUT, PHP_EOL . 'NOTIFICATION_TEMPLATE_UI_OK' . PHP_EOL);
    }
}
