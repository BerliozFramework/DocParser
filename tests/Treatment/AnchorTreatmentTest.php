<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\DocParser\Tests\Treatment;

use Berlioz\DocParser\Doc\Documentation;
use Berlioz\DocParser\Doc\File\Page;
use Berlioz\DocParser\Doc\File\RawFile;
use Berlioz\DocParser\DocGenerator;
use Berlioz\DocParser\Parser\Markdown;
use Berlioz\DocParser\Treatment\AnchorTreatment;
use Berlioz\DocParser\Treatment\PageSummaryTreatment;
use Berlioz\HtmlSelector\HtmlSelector;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class AnchorTreatmentTest extends TestCase
{
    private const QUEUES_PAGE = <<<'HTML'
        <h1>Queue Manager</h1>
        <h2>Queues</h2>
        <h4>DbQueue</h4>
        <h3>Options</h3>
        <h2>Worker</h2>
        <h3>Options</h3>
        <h3>Remote Stop (Kill File)</h3>
        <h2>CLI commands</h2>
        <h3><code>queue:worker</code></h3>
        <p>
          <a href="#dbqueue">nested</a>
          <a href="#queues">top level</a>
          <a href="#queues-dbqueue">site id</a>
          <a href="#options">first duplicate</a>
          <a href="#options-1">second duplicate</a>
          <a href="#remote-stop-kill-file">punctuation</a>
          <a href="#queueworker">inline code</a>
          <a href="#unknown">unknown</a>
          <a href="#">empty</a>
          <a href="https://example.com/page#dbqueue">external</a>
          <a href="//example.com/page#dbqueue">protocol relative</a>
          <a href="mailto:someone@example.com">mail</a>
        </p>
        HTML;

    private function createPage(string $filename, string $html): Page
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $html);
        rewind($stream);

        return new Page($stream, $filename, 'text/html');
    }

    private function createDocumentation(Page|RawFile ...$files): Documentation
    {
        $documentation = new Documentation('test');

        foreach ($files as $file) {
            $documentation->getFiles()->addFile($file);
        }

        (new PageSummaryTreatment())->handle($documentation);
        (new AnchorTreatment())->handle($documentation);

        return $documentation;
    }

    private function getHref(Page $page, string $linkText): ?string
    {
        $link = (new HtmlSelector())->query($page->getContents())->find(sprintf('a:contains(%s)', $linkText));
        $this->assertCount(1, $link, sprintf('Link "%s" not found', $linkText));

        return $link->attr('href');
    }

    public function testFragmentOfNestedHeadingIsResolved()
    {
        $page = $this->createPage('components/queue-manager.md', self::QUEUES_PAGE);
        $this->createDocumentation($page);

        $this->assertEquals('#queues-dbqueue', $this->getHref($page, 'nested'));
        $this->assertEquals('#worker-remote-stop-kill-file', $this->getHref($page, 'punctuation'));
        $this->assertEquals('#cli-commands-queueworker', $this->getHref($page, 'inline code'));
    }

    public function testFragmentMatchingAnIdIsKept()
    {
        $page = $this->createPage('components/queue-manager.md', self::QUEUES_PAGE);
        $this->createDocumentation($page);

        $this->assertEquals('#queues', $this->getHref($page, 'top level'));
        $this->assertEquals('#queues-dbqueue', $this->getHref($page, 'site id'));
    }

    public function testDuplicateHeadingsAreNumberedLikeGitHub()
    {
        $page = $this->createPage('components/queue-manager.md', self::QUEUES_PAGE);
        $this->createDocumentation($page);

        $this->assertEquals('#queues-options', $this->getHref($page, 'first duplicate'));
        $this->assertEquals('#worker-options', $this->getHref($page, 'second duplicate'));
    }

    public function testFragmentOfHeadingWithExistingIdIsResolved()
    {
        $page = $this->createPage(
            'index.md',
            '<h2 id="custom">Pinned heading</h2><h2>Without id</h2>' .
            '<a href="#pinned-heading">pinned</a><a href="#without-id">without id</a>'
        );
        $documentation = new Documentation('test');
        $documentation->getFiles()->addFile($page);

        (new AnchorTreatment())->handle($documentation);

        $this->assertEquals('#custom', $this->getHref($page, 'pinned'));
        $this->assertEquals('#without-id', $this->getHref($page, 'without id'));
    }

    public function testUnknownFragmentAndExternalLinksAreKept()
    {
        $page = $this->createPage('components/queue-manager.md', self::QUEUES_PAGE);
        $this->createDocumentation($page);

        $this->assertEquals('#unknown', $this->getHref($page, 'unknown'));
        $this->assertEquals('#', $this->getHref($page, 'empty'));
        $this->assertEquals('https://example.com/page#dbqueue', $this->getHref($page, 'external'));
        $this->assertEquals('//example.com/page#dbqueue', $this->getHref($page, 'protocol relative'));
        $this->assertEquals('mailto:someone@example.com', $this->getHref($page, 'mail'));
    }

    public function testFragmentOfLinkToAnotherPageIsResolved()
    {
        $target = $this->createPage('components/queue-manager.md', self::QUEUES_PAGE);
        $sibling = $this->createPage(
            'components/router.md',
            '<a href="./queue-manager#dbqueue">sibling</a>' .
            '<a href="queue-manager#options-1">bare</a>' .
            '<a href="./queue-manager">without fragment</a>' .
            '<a href="./missing#dbqueue">missing page</a>'
        );
        $other = $this->createPage(
            'guides/with space/my page.md',
            '<a href="../../components/queue-manager#dbqueue">other directory</a>' .
            '<a href="../../../../outside#dbqueue">above root</a>'
        );
        $this->createDocumentation($target, $sibling, $other);

        $this->assertEquals('./queue-manager#queues-dbqueue', $this->getHref($sibling, 'sibling'));
        $this->assertEquals('queue-manager#worker-options', $this->getHref($sibling, 'bare'));
        $this->assertEquals('./queue-manager', $this->getHref($sibling, 'without fragment'));
        $this->assertEquals('./missing#dbqueue', $this->getHref($sibling, 'missing page'));
        $this->assertEquals(
            '../../components/queue-manager#queues-dbqueue',
            $this->getHref($other, 'other directory')
        );
        $this->assertEquals('../../../../outside#dbqueue', $this->getHref($other, 'above root'));
    }

    public function testFragmentOfLinkToPageWithEncodedPathIsResolved()
    {
        $target = $this->createPage('with space/other page.md', '<h2>Section</h2><h3>Sub section</h3>');
        $page = $this->createPage('with space/my page.md', '<a href="./other%20page#sub-section">encoded</a>');
        $this->createDocumentation($target, $page);

        $this->assertEquals('./other%20page#section-sub-section', $this->getHref($page, 'encoded'));
    }

    public function testFragmentOfLinkToRawFileIsKept()
    {
        $stream = fopen('php://memory', 'r+');
        $rawFile = new RawFile($stream, 'assets/file.pdf', 'application/pdf');
        $page = $this->createPage('index.md', '<a href="assets/file.pdf#page=2">raw file</a>');
        $this->createDocumentation($page, $rawFile);

        $this->assertEquals('assets/file.pdf#page=2', $this->getHref($page, 'raw file'));
    }

    public function testPageWithoutFragmentIsNotRewritten()
    {
        $html = '<p>No   fragment <a href="./other">here</a></p>';
        $page = $this->createPage('index.md', $html);
        $documentation = new Documentation('test');
        $documentation->getFiles()->addFile($page);

        (new AnchorTreatment())->handle($documentation);

        $this->assertSame($html, $page->getContents());
    }

    public function testDocGeneratorResolvesGitHubAnchors()
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $filesystem->write(
            'guides/queues.md',
            <<<'MARKDOWN'
                # Queues

                ## Configuration

                ### Redis queue

                See [DbQueue](../components/queue-manager.md#dbqueue) and [Redis queue](#redis-queue).
                MARKDOWN
        );
        $filesystem->write(
            'components/queue-manager.md',
            <<<'MARKDOWN'
                # Queue Manager

                ## Queues

                #### DbQueue
                MARKDOWN
        );

        $docGenerator = new DocGenerator();
        $docGenerator->addParser(new Markdown());
        $documentation = $docGenerator->handle('test', $filesystem);

        /** @var Page $page */
        $page = $documentation->getFiles()->findByPath('guides/queues');
        $this->assertNotNull($page);

        $this->assertEquals('../components/queue-manager#queues-dbqueue', $this->getHref($page, 'DbQueue'));
        $this->assertEquals('#configuration-redis-queue', $this->getHref($page, 'Redis queue'));
    }
}
