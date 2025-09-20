import { Component, OnInit } from '@angular/core';
import { ChatbotService } from '../../services/chatbot.service';

export interface User {
  name: string;
  email: string;
}

export interface ChatMessage {
  type: 'bot' | 'user';
  content: string;
  timestamp: Date;
}

export interface ChatOption {
  id: string;
  text: string;
  link?: string;
}

@Component({
  selector: 'app-chatbot',
  templateUrl: './chatbot.component.html',
  styleUrls: ['./chatbot.component.scss']
})
export class ChatbotComponent implements OnInit {
  isOpen = false;
  showUserForm = true;
  currentUser: User = { name: '', email: '' };
  messages: ChatMessage[] = [];
  currentOptions: ChatOption[] = [];
  
  constructor(private chatbotService: ChatbotService) {}

  ngOnInit() {
    // Initialize chatbot data
  }

  toggleChatbot() {
    this.isOpen = !this.isOpen;
  }

  openChatbot() {
    this.isOpen = true;
  }

  closeChatbot() {
    this.isOpen = false;
  }

  async startChat(userData: User) {
    if (!userData.name || !userData.email) {
      return;
    }

    if (!this.isValidEmail(userData.email)) {
      return;
    }

    try {
      // Save user data to backend
      await this.chatbotService.saveUser(userData).toPromise();
      
      this.currentUser = userData;
      this.showUserForm = false;
      
      // Start conversation
      this.addBotMessage(`Hello ${userData.name}! 👋<br>Hey there! Please select an option to get started.`);
      this.showMainOptions();
    } catch (error) {
      console.error('Error saving user:', error);
    }
  }

  private isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  private addBotMessage(content: string) {
    this.messages.push({
      type: 'bot',
      content,
      timestamp: new Date()
    });
  }

  private addUserMessage(content: string) {
    this.messages.push({
      type: 'user',
      content,
      timestamp: new Date()
    });
  }

  private async showMainOptions() {
    try {
      const options = await this.chatbotService.getMainOptions().toPromise();
      this.currentOptions = options!;
    } catch (error) {
      console.error('Error loading main options:', error);
    }
  }

  async handleMainOption(optionId: string, optionText: string) {
    this.addUserMessage(optionText);
    this.currentOptions = [];

    try {
      switch(optionId) {
        case 'courses':
          await this.showCourses();
          break;
        case 'internships':
          await this.showInternships();
          break;
        case 'contact':
          await this.showContactInfo();
          break;
      }
    } catch (error) {
      console.error('Error handling option:', error);
    }
  }

  private async showCourses() {
    this.addBotMessage('Great! Here are our available courses:');
    const courses = await this.chatbotService.getCourses().toPromise();
    this.currentOptions = courses!;
  }

  private async showInternships() {
    this.addBotMessage('Excellent! Here are our internship opportunities:');
    const internships = await this.chatbotService.getInternships().toPromise();
    this.currentOptions = internships!;
  }

  private async showContactInfo() {
    const contact = await this.chatbotService.getContactInfo().toPromise();
    this.addBotMessage(`
      <div class="contact-info">
        <strong>Contact Information</strong><br>
        <p><i class="bi bi-telephone"></i> Phone: ${contact!.phone}</p>
        <p><i class="bi bi-envelope"></i> Email: ${contact!.email}</p>
      </div>
    `);
  }

  showCourseDetails(course: ChatOption) {
    this.addUserMessage(course.text);
    this.addBotMessage(`
      <div class="course-link">
        <strong>${course.text}</strong><br>
        <a href="${course.link}" target="_blank">Click here for more details about ${course.text}</a>
      </div>
    `);
    this.currentOptions = [];
  }

  showInternshipDetails(internship: ChatOption) {
    this.addUserMessage(internship.text);
    this.addBotMessage(`
      <div class="internship-link">
        <strong>${internship.text} Internship</strong><br>
        <a href="${internship.link}" target="_blank">Click here for more details about ${internship.text} internship</a>
      </div>
    `);
    this.currentOptions = [];
  }

  restartChat() {
    this.messages = [];
    this.currentOptions = [];
    this.addBotMessage(`Hello ${this.currentUser.name}! 👋<br>Hey there! Please select an option to get started.`);
    this.showMainOptions();
  }
}