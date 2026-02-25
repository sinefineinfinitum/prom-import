<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;

class SettingController extends BaseController
{
    public function __construct(
        public OptionRepositoryInterface $optionRepository,
    ){
    }
	public function settings_page_content(): void
    {
		$spssUrl = $this->optionRepository->getOption( XmlService::SINEFINE_PROMIMPORT_URL_OPTION, '');
        $this->render( 'settings', ['spssUrl' => $spssUrl] );
	}
}