import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { User, ChatOption } from '../components/chatbot/chatbot.component';

export interface ContactInfo {
  phone: string;
  email: string;
}

@Injectable({
  providedIn: 'root'
})
export class ChatbotService {
  private apiUrl = 'http://localhost:8080/api';

  constructor(private http: HttpClient) {}

  saveUser(user: User): Observable<any> {
    return this.http.post(`${this.apiUrl}/save-user.php`, user);
  }

  getMainOptions(): Observable<ChatOption[]> {
    return this.http.get<ChatOption[]>(`${this.apiUrl}/get-main-options.php`);
  }

  getCourses(): Observable<ChatOption[]> {
    return this.http.get<ChatOption[]>(`${this.apiUrl}/get-courses.php`);
  }

  getInternships(): Observable<ChatOption[]> {
    return this.http.get<ChatOption[]>(`${this.apiUrl}/get-internships.php`);
  }

  getContactInfo(): Observable<ContactInfo> {
    return this.http.get<ContactInfo>(`${this.apiUrl}/get-contact.php`);
  }
}