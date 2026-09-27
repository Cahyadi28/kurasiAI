<?php

declare(strict_types=1);

namespace KurasiAI\Tests\Unit\Http;

use InvalidArgumentException;
use KurasiAI\Http\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        $this->view = new View(__DIR__ . '/../../Fixtures/views', ['appName' => 'KurasiAI']);
    }

    public function testEscapesEverythingThatCanBreakHtml(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; &apos;kutip&apos;',
            $this->view->e('<script>alert("x")</script> & \'kutip\''),
        );
        $this->assertSame('42', $this->view->e(42));
        $this->assertSame('', $this->view->e(null));
    }

    public function testRendersInsideTheLayoutWithSharedData(): void
    {
        $html = $this->view->render('greeting', ['name' => '<b>Rina</b>', 'title' => '" onmouseover="alert(1)']);

        $this->assertSame(
            '<main data-app="KurasiAI"><p title="&quot; onmouseover=&quot;alert(1)">Halo, &lt;b&gt;Rina&lt;/b&gt;</p>' . "\n" . '</main>' . "\n",
            $html,
        );
    }

    public function testRejectsViewNamesThatPointElsewhere(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->view->render('../../../.env');
    }
}
