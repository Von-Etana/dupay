// lib/models/auth/register_request.dart
//
// Updated to match the backend /register endpoint, which expects
// multipart/form-data with all of the following fields:
//
//   first_name, last_name, username, phone_number, email,
//   password, password_confirmation, bvn, date_of_birth, mobile_no,
//   address, city, state, zip_code, referral, fcmToken, other_names,
//   method (BVN | NIN), gender, house_number, issued_country,
//   issued_date, expiration_date,
//   id_image  (file),
//   user_photo (file).
//
import 'dart:io';
import 'package:dio/dio.dart';

class RegisterRequest {
  // Basic account fields
  final String firstName;
  final String lastName;
  final String username;
  final String phoneNumber;
  final String email;
  final String password;
  final String passwordConfirmation;
  final String? referral;
  final String? fcmToken;

  // KYC / wallet verification fields
  final String bvn;
  final String dateOfBirth; // yyyy-MM-dd
  final String mobileNo;
  final String address;
  final String city;
  final String state;
  final String zipCode;
  final String? otherNames;
  final String method; // "BVN" or "NIN"
  final String gender; // "Male" or "Female"
  final String houseNumber;
  final String issuedCountry;
  final String? issuedDate;
  final String? expirationDate;

  // Files
  final File? idImage;
  final File? userPhoto;

  RegisterRequest({
    required this.firstName,
    required this.lastName,
    required this.username,
    required this.phoneNumber,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    this.referral,
    this.fcmToken,
    required this.bvn,
    required this.dateOfBirth,
    required this.mobileNo,
    required this.address,
    required this.city,
    required this.state,
    required this.zipCode,
    this.otherNames,
    this.method = 'BVN',
    required this.gender,
    required this.houseNumber,
    this.issuedCountry = 'Nigeria',
    this.issuedDate,
    this.expirationDate,
    this.idImage,
    this.userPhoto,
  });

  /// JSON map (used for debug/logging only — the actual request uses FormData).
  Map<String, dynamic> toJson() {
    return {
      'first_name': firstName,
      'last_name': lastName,
      'username': username,
      'phone_number': phoneNumber,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'bvn': bvn,
      'date_of_birth': dateOfBirth,
      'mobile_no': mobileNo,
      'address': address,
      'city': city,
      'state': state,
      'zip_code': zipCode,
      if (referral != null && referral!.isNotEmpty) 'referral': referral,
      if (fcmToken != null && fcmToken!.isNotEmpty) 'fcmToken': fcmToken,
      'other_names': otherNames ?? '',
      'method': method,
      'gender': gender,
      'house_number': houseNumber,
      'issued_country': issuedCountry,
      if (issuedDate != null) 'issued_date': issuedDate,
      if (expirationDate != null) 'expiration_date': expirationDate,
      'id_image': idImage?.path,
      'user_photo': userPhoto?.path,
    };
  }

  /// The backend expects multipart/form-data. Build it here.
  Future<FormData> toFormData() async {
    final Map<String, dynamic> fields = {
      'first_name': firstName,
      'last_name': lastName,
      'username': username,
      'phone_number': phoneNumber,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'bvn': bvn,
      'date_of_birth': dateOfBirth,
      'mobile_no': mobileNo,
      'address': address,
      'city': city,
      'state': state,
      'zip_code': zipCode,
      'referral': referral ?? '',
      'fcmToken': fcmToken ?? '',
      'other_names': otherNames ?? '',
      'method': method,
      'gender': gender,
      'house_number': houseNumber,
      'issued_country': issuedCountry,
      'issued_date': issuedDate ?? '',
      'expiration_date': expirationDate ?? '',
    };

    if (idImage != null) {
      fields['id_image'] = await MultipartFile.fromFile(
        idImage!.path,
        filename: idImage!.path.split(Platform.pathSeparator).last,
      );
    }
    if (userPhoto != null) {
      fields['user_photo'] = await MultipartFile.fromFile(
        userPhoto!.path,
        filename: userPhoto!.path.split(Platform.pathSeparator).last,
      );
    }

    return FormData.fromMap(fields);
  }

  factory RegisterRequest.fromJson(Map<String, dynamic> json) {
    return RegisterRequest(
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      username: json['username'] ?? '',
      phoneNumber: json['phone_number'] ?? '',
      email: json['email'] ?? '',
      password: json['password'] ?? '',
      passwordConfirmation: json['password_confirmation'] ?? '',
      referral: json['referral'],
      fcmToken: json['fcmToken'],
      bvn: json['bvn'] ?? '',
      dateOfBirth: json['date_of_birth'] ?? '',
      mobileNo: json['mobile_no'] ?? '',
      address: json['address'] ?? '',
      city: json['city'] ?? '',
      state: json['state'] ?? '',
      zipCode: json['zip_code'] ?? '',
      otherNames: json['other_names'],
      method: json['method'] ?? 'BVN',
      gender: json['gender'] ?? 'Male',
      houseNumber: json['house_number'] ?? '',
      issuedCountry: json['issued_country'] ?? 'Nigeria',
      issuedDate: json['issued_date'],
      expirationDate: json['expiration_date'],
    );
  }
}