<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:hello',
    description: 'Add a short description for your command',
)]
class HelloCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // 1. Добавляем аргумент (обязательный параметр)
        $this->addArgument(
            'name',                    // Имя аргумента
            InputArgument::REQUIRED,   // Обязательный
            'Имя пользователя'         // Описание
        );

        // 2. Добавляем опцию (необязательный параметр)
        $this->addOption(
            'times',                   // Имя опции  
            't',                       // Короткое имя (--times или -t)
            InputOption::VALUE_OPTIONAL, // Необязательная
            'Сколько раз повторить',   // Описание
            1                          // Значение по умолчанию
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // 3. Получаем переданные значения
        $name = $input->getArgument('name');
        $times = $input->getOption('times');
        
        $io->note("Приветствуем: {$name}");
        $io->note("Количество раз: {$times}");
        
        // 4. Повторяем приветствие
        for ($i = 0; $i < $times; $i++) {
            $io->text("Привет, {$name}! (#" . ($i + 1) . ")");
        }
        
        $io->success('Готово!');
        return Command::SUCCESS;
    }
}
