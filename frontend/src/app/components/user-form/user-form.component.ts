import { Component, EventEmitter, Output } from '@angular/core';
import { NgForm } from '@angular/forms';
import { User } from '../chatbot/chatbot.component';

@Component({
  selector: 'app-user-form',
  templateUrl: './user-form.component.html',
  styleUrls: ['./user-form.component.scss']
})
export class UserFormComponent {
  @Output() userSubmitted = new EventEmitter<User>();
  
  user: User = { name: '', email: '' };

  onSubmit(form: NgForm) {
    if (form.valid && this.isValidEmail(this.user.email)) {
      this.userSubmitted.emit({ ...this.user });
    }
  }

  private isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }
}