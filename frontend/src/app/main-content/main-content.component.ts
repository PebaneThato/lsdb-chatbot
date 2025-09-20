import { Component, EventEmitter, Output } from '@angular/core';

@Component({
  selector: 'app-main-content',
  templateUrl: './main-content.component.html',
  styleUrls: ['./main-content.component.scss']
})
export class MainContentComponent {
  @Output() openChatbot = new EventEmitter<void>();

  onOpenChatbot() {
    this.openChatbot.emit();
  }
}